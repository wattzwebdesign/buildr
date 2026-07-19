<?php

namespace Buildr\Tests;

use Buildr\Http\Livewire\Editor;
use Buildr\Models\Page;
use Buildr\Render\PageRenderer;
use Livewire\Livewire;

class NewElementsTest extends TestCase
{
    private function pageWith(string ...$types): Page
    {
        $page = Page::create(['title' => 'T', 'slug' => 't', 'published_at' => now()]);

        $component = Livewire::test(Editor::class, ['page' => $page])->call('addContainer', 1);
        foreach ($types as $type) {
            $component->call('addElement', $type);
        }

        return $page->fresh();
    }

    public function test_every_registered_element_renders_with_defaults(): void
    {
        $types = ['video', 'map', 'html', 'star_rating', 'icon_box', 'icon_list',
            'social_icons', 'accordion', 'tabs', 'gallery', 'form'];

        $page = $this->pageWith(...$types);
        $result = app(PageRenderer::class)->render($page);

        foreach (['b-video', 'b-map', 'b-stars', 'b-iconbox', 'b-iconlist',
            'b-social', 'b-accordion', 'b-tabs', 'b-gallery', 'b-form'] as $marker) {
            $this->assertStringContainsString($marker, $result['html'], $marker);
        }
    }

    public function test_editor_renders_style_tabs_for_all_new_elements(): void
    {
        $page = $this->pageWith('icon_list', 'accordion', 'tabs', 'gallery', 'form', 'star_rating');

        $component = Livewire::test(Editor::class, ['page' => $page]);
        foreach ($page->nodes()->whereNotNull('parent_id')->get() as $node) {
            $component->call('selectNode', $node->id)
                ->assertOk()
                ->call('setTab', 'style')->assertOk()
                ->call('setTab', 'advanced')->assertOk();
        }
    }

    public function test_repeater_add_and_remove_items(): void
    {
        $page = $this->pageWith('icon_list');
        $list = $page->nodes()->where('type', 'icon_list')->first();

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $list->id);

        $this->assertCount(3, $list->fresh()->setting('content', 'items')); // defaults

        $component->call('addRepeaterItem', 'content', 'items')
            ->set('settings.content.items.3.text', 'Fourth item');
        $this->assertCount(4, $list->fresh()->setting('content', 'items'));
        $this->assertSame('Fourth item', $list->fresh()->setting('content', 'items')[3]['text']);

        $component->call('removeRepeaterItem', 'content', 'items', 0);
        $this->assertCount(3, $list->fresh()->setting('content', 'items'));
    }

    public function test_accordion_renders_native_details_with_exclusive_group(): void
    {
        $page = $this->pageWith('accordion');
        $html = app(PageRenderer::class)->render($page)['html'];

        $this->assertSame(2, substr_count($html, '<details'));
        $this->assertStringContainsString('name="acc-', $html);
        $this->assertStringContainsString('open', $html);
    }

    public function test_tabs_render_without_javascript(): void
    {
        $page = $this->pageWith('tabs');
        $result = app(PageRenderer::class)->render($page);

        $this->assertSame(2, substr_count($result['html'], 'tb-panel"'));
        $this->assertStringNotContainsString('<script', $result['html']);
        $this->assertStringContainsString(':checked ~ .tb-panels', $result['css']);
    }

    public function test_video_url_becomes_youtube_embed(): void
    {
        $page = $this->pageWith('video');
        $video = $page->nodes()->where('type', 'video')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $video->id)
            ->set('settings.content.url', 'https://www.youtube.com/watch?v=abc123XYZ');

        $html = app(PageRenderer::class)->render($page->fresh())['html'];
        $this->assertStringContainsString('youtube-nocookie.com/embed/abc123XYZ', $html);
    }

    public function test_form_submission_stores_payload_and_redirects(): void
    {
        $page = $this->pageWith('form');
        $form = $page->nodes()->where('type', 'form')->first();

        $response = $this->from('/t')->post("/buildr-form/{$form->id}", [
            'f0' => 'Don', 'f1' => 'don@example.com', 'f2' => 'Hello there',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('sent='.$form->cssId(), $response->headers->get('Location'));

        $submission = $page->formSubmissions()->first();
        $this->assertSame('Don', $submission->payload['Name']);
        $this->assertSame('don@example.com', $submission->payload['Email']);
    }

    public function test_global_colors_and_fonts_compile_to_css_layer(): void
    {
        \Buildr\Models\SiteSetting::set('colors', [
            ['name' => 'Primary', 'value' => '#14324f'],
            ['name' => 'Accent', 'value' => '#e8a33d'],
        ]);
        \Buildr\Models\SiteSetting::set('font_heading', 'Fraunces');
        \Buildr\Models\SiteSetting::set('font_heading_weight', 600);
        \Buildr\Models\SiteSetting::set('font_body', 'Archivo');

        $css = \Buildr\Render\GlobalCss::css();
        $this->assertStringContainsString('--g-primary:#14324f', $css);
        $this->assertStringContainsString('--g-accent:#e8a33d', $css);
        $this->assertStringContainsString('font-family:"Fraunces"', $css);
        $this->assertStringContainsString('font-family:"Archivo"', $css);

        $link = \Buildr\Render\GlobalCss::fontLink();
        $this->assertStringContainsString('fonts.googleapis.com', $link);
        $this->assertStringContainsString('Fraunces', $link);

        // element referencing a global var renders it through the compiler
        $page = $this->pageWith('heading');
        $heading = $page->nodes()->where('type', 'heading')->first();
        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $heading->id)
            ->set('settings.style.color', 'var(--g-primary)');

        $compiled = app(PageRenderer::class)->render($page->fresh())['css'];
        $this->assertStringContainsString('color:var(--g-primary)', $compiled);
    }

    public function test_richtext_plain_text_becomes_paragraphs_html_passes_through(): void
    {
        $this->assertSame(
            "<p>First para</p><p>Line one<br>\nLine two</p>",
            \Buildr\Support\Richtext::render("First para\n\nLine one\nLine two")
        );
        $this->assertSame(
            '<p>Custom <strong>markup</strong></p>',
            \Buildr\Support\Richtext::render('<p>Custom <strong>markup</strong></p>')
        );
        $this->assertSame('<p>Safe &lt;3 chars</p>', \Buildr\Support\Richtext::render('Safe <3 chars'));
    }

    public function test_icon_box_per_part_typography_compiles_scoped(): void
    {
        $page = $this->pageWith('icon_box');
        $box = $page->nodes()->where('type', 'icon_box')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $box->id)
            ->call('setTab', 'style')
            ->set('settings.style.heading_color', '#14324f')
            ->set('settings.style.heading_font_size.desktop.value', 28)
            ->set('settings.style.text_font_size.desktop.value', 14)
            ->set('settings.style.heading_text_transform', 'uppercase');

        $css = app(PageRenderer::class)->render($page->fresh())['css'];

        $this->assertStringContainsString(':where(h3){', $css);
        $this->assertStringContainsString('color:#14324f', $css);
        $this->assertStringContainsString('font-size:28px', $css);
        $this->assertStringContainsString('text-transform:uppercase', $css);
        $this->assertMatchesRegularExpression('/:where\(\.ib-body\)\{[^}]*font-size:14px/', $css);
    }

    public function test_form_required_field_rejects_empty(): void
    {
        $page = $this->pageWith('form');
        $form = $page->nodes()->where('type', 'form')->first();

        $this->from('/t')->post("/buildr-form/{$form->id}", ['f0' => '', 'f1' => ''])
            ->assertRedirect('/t');

        $this->assertSame(0, $page->formSubmissions()->count());
    }
}
