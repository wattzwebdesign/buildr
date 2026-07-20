<?php

namespace Buildr\Tests;

use Buildr\Http\Livewire\Editor;
use Buildr\Models\Page;
use Livewire\Livewire;

class EditorTest extends TestCase
{
    private function pageWithHero(): Page
    {
        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'published_at' => now()]);

        $container = $page->nodes()->create([
            'type' => 'container', 'sort' => 0,
            'data' => ['content' => ['widths' => [100], 'tag' => 'section']],
        ]);

        $page->nodes()->create([
            'type' => 'heading', 'parent_id' => $container->id, 'sort' => 0,
            'data' => ['content' => ['text' => 'Original headline', 'tag' => 'h1']],
        ]);

        return $page;
    }

    public function test_editor_page_renders_with_sections(): void
    {
        $page = $this->pageWithHero();

        $this->get("/buildr/pages/{$page->id}/edit")
            ->assertOk()
            ->assertSee('Original headline')   // canvas renders
            ->assertSee('Search elements');    // library is the home view
    }

    public function test_selecting_a_node_loads_normalized_settings(): void
    {
        $page = $this->pageWithHero();
        $heading = $page->nodes()->where('type', 'heading')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $heading->id)
            ->assertSet('settings.content.text', 'Original headline')
            // responsive unit field normalizes to per-device {value, unit}
            ->assertSet('settings.style.font_size.desktop.unit', 'px')
            ->assertSet('settings.advanced.padding.desktop.top.unit', 'px');
    }

    public function test_editing_text_persists_and_rerenders(): void
    {
        $page = $this->pageWithHero();
        $heading = $page->nodes()->where('type', 'heading')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $heading->id)
            ->set('settings.content.text', 'New headline')
            ->assertSee('New headline');

        $this->assertSame('New headline', $heading->fresh()->setting('content', 'text'));
    }

    public function test_responsive_unit_edit_lands_in_compiled_css(): void
    {
        $page = $this->pageWithHero();
        $heading = $page->nodes()->where('type', 'heading')->first();

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $heading->id)
            ->set('settings.style.color', '#14324f')
            ->set('settings.style.font_size.desktop.value', 52)
            ->call('setDevice', 'mobile')
            ->set('settings.style.font_size.mobile.value', 30);

        $css = $this->publishedRender($page)['css'];

        $this->assertStringContainsString('font-size:52px', $css);
        $this->assertStringContainsString('color:#14324f', $css);
        $this->assertStringContainsString('@media(max-width:640px)', $css);
        $this->assertStringContainsString('font-size:30px', $css);
    }

    public function test_sides_control_and_shared_unit(): void
    {
        $page = $this->pageWithHero();
        $heading = $page->nodes()->where('type', 'heading')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $heading->id)
            ->set('settings.advanced.padding.desktop.top.value', 2)
            ->set('settings.advanced.padding.desktop.bottom.value', 3)
            ->call('setSidesUnit', 'advanced', 'padding', 'em', 'desktop');

        $css = $this->publishedRender($page)['css'];

        $this->assertStringContainsString('padding-top:2em', $css);
        $this->assertStringContainsString('padding-bottom:3em', $css);
    }

    public function test_container_border_compiles_to_valid_css(): void
    {
        $page = $this->pageWithHero();
        $container = $page->nodes()->where('type', 'container')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $container->id)
            ->set('settings.style.border_style', 'solid')
            ->set('settings.style.border_color', '#000000')
            ->set('settings.style.border_width.desktop.top.value', 5);

        $css = $this->publishedRender($page)['css'];

        $this->assertStringContainsString('border-top-width:5px', $css);
        $this->assertStringContainsString('border-style:solid', $css);
        $this->assertStringContainsString('border-color:#000000', $css);
        $this->assertStringNotContainsString('border-width-top', $css);
    }

    public function test_container_width_presets(): void
    {
        $page = $this->pageWithHero();
        $container = $page->nodes()->where('type', 'container')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $container->id)
            ->call('setWidths', '33,67');

        $css = $this->publishedRender($page)['css'];

        $this->assertStringContainsString('grid-template-columns:33fr 67fr', $css);
    }

    public function test_editor_canvas_previews_breakpoints_via_container_queries(): void
    {
        $page = $this->pageWithHero();
        $container = $page->nodes()->where('type', 'container')->first();
        $container->update(['data' => ['content' => ['widths' => [50, 50], 'tag' => 'section', 'stack_mobile' => true]]]);

        // editor canvas: container queries (frame width drives the preview)
        Livewire::test(Editor::class, ['page' => $page->fresh()])
            ->assertSee('@container (max-width:640px)', false)
            ->assertDontSee('@media(max-width:640px)', false);

        // published page keeps real viewport media queries
        $css = $this->publishedRender($page->fresh())['css'];
        $this->assertStringContainsString('@media(max-width:640px)', $css);
        $this->assertStringContainsString('grid-template-columns:1fr;', $css);
    }

    public function test_dynamic_tags_resolve_in_attributes_and_nested_fields(): void
    {
        \Buildr\Models\SiteSetting::set('phone', '(410) 555-0114');

        $page = $this->pageWithHero();
        $container = $page->nodes()->where('type', 'container')->first();
        $heading = $page->nodes()->where('type', 'heading')->first();

        // tags in advanced attributes (anchor id + css classes)
        $heading->update(['data' => array_merge($heading->data, [
            'advanced' => ['anchor_id' => 'sec-{{page.slug}}', 'css_class' => 'promo-{{year}}'],
        ])]);

        // tag nested inside a link field
        $page->nodes()->create([
            'type' => 'button', 'parent_id' => $container->id, 'sort' => 5,
            'data' => ['content' => ['label' => 'Call us', 'link' => ['url' => '{{site.phone_link}}']]],
        ]);

        $html = $this->publishedRender($page)['html'];
        $this->assertStringContainsString('id="sec-home"', $html);
        $this->assertStringContainsString('promo-'.now()->format('Y'), $html);
        $this->assertStringContainsString('href="tel:4105550114"', $html);
    }

    public function test_undo_redo_walk_tree_and_setting_mutations(): void
    {
        $page = $this->pageWithHero();
        $container = $page->nodes()->where('type', 'container')->first();

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $container->id)
            ->call('addElement', 'button');
        $this->assertSame(1, $page->nodes()->where('type', 'button')->count());

        // undo removes the button, redo brings it back
        $component->call('undo');
        $this->assertSame(0, $page->nodes()->where('type', 'button')->count());
        $component->call('redo');
        $this->assertSame(1, $page->nodes()->where('type', 'button')->count());

        // setting edits are undoable too — a burst of edits is one step
        $heading = $page->nodes()->where('is_draft', true)->where('type', 'heading')->first();
        $component->call('selectNode', $heading->id)
            ->set('settings.content.text', 'Changed once')
            ->set('settings.content.text', 'Changed twice');
        $component->call('undo');
        $fresh = $page->nodes()->where('is_draft', true)->where('type', 'heading')->first();
        $this->assertSame('Original headline', $fresh->setting('content', 'text'));

        // deleting is undoable
        $fresh2 = $page->nodes()->where('is_draft', true)->where('type', 'heading')->first();
        $component->call('deleteNode', $fresh2->id);
        $this->assertSame(0, $page->nodes()->where('is_draft', true)->where('type', 'heading')->count());
        $component->call('undo');
        $this->assertSame(1, $page->nodes()->where('is_draft', true)->where('type', 'heading')->count());

        // a fresh mutation clears the redo stack
        $component->call('addElement', 'divider')->call('undo')->call('addElement', 'spacer');
        $component->call('redo'); // nothing to redo — divider must not reappear
        $this->assertSame(0, $page->nodes()->where('type', 'divider')->count());
        $this->assertSame(1, $page->nodes()->where('type', 'spacer')->count());
    }

    public function test_responsive_button_padding_compiles_per_device(): void
    {
        $page = $this->pageWithHero();
        $container = $page->nodes()->where('type', 'container')->first();

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $container->id)
            ->call('addElement', 'button');
        $button = $page->nodes()->where('type', 'button')->first();

        $component->call('selectNode', $button->id)
            ->call('setTab', 'style')
            ->set('settings.style.padding.desktop.top.value', 12)
            ->call('setDevice', 'mobile')
            ->set('settings.style.padding.mobile.top.value', 8);

        $css = $this->publishedRender($page)['css'];
        $this->assertStringContainsString('padding-top:12px', $css);
        $this->assertMatchesRegularExpression('/@media\(max-width:640px\).*padding-top:8px/s', $css);
    }

    public function test_site_settings_save_only_on_explicit_save(): void
    {
        $page = $this->pageWithHero();

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('openSite')
            ->set('site.name', 'Changed Name')
            ->assertSet('siteDirty', true);

        $this->assertNull(\Buildr\Models\SiteSetting::get('name'));

        $component->call('saveSite')->assertSet('siteDirty', false);
        $this->assertSame('Changed Name', \Buildr\Models\SiteSetting::get('name'));
    }

    public function test_publish_from_editor(): void
    {
        $page = $this->pageWithHero();
        $page->update(['published_at' => null]);

        Livewire::test(Editor::class, ['page' => $page])->call('publish');

        $this->assertTrue($page->fresh()->isPublished());
    }
}
