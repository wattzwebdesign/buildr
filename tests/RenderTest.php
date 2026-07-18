<?php

namespace Buildr\Tests;

use Buildr\Models\Page;
use Buildr\Render\PageRenderer;

class RenderTest extends TestCase
{
    private function makePage(): Page
    {
        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'published_at' => now()]);

        $container = $page->nodes()->create([
            'type' => 'container',
            'sort' => 0,
            'data' => [
                'content' => [
                    'widths' => [33, 67],
                    'gap' => ['value' => 24, 'unit' => 'px'],
                    'tag' => 'section',
                ],
                'style' => ['background' => '#f7f9fa'],
            ],
        ]);

        $page->nodes()->create([
            'type' => 'heading',
            'parent_id' => $container->id,
            'sort' => 0,
            'data' => [
                'content' => ['text' => 'Roofing done right', 'tag' => 'h1'],
                'style' => [
                    'color' => '#14324f',
                    'font_size' => [
                        'desktop' => ['value' => 44, 'unit' => 'px'],
                        'mobile' => ['value' => 30, 'unit' => 'px'],
                    ],
                ],
            ],
        ]);

        $page->nodes()->create([
            'type' => 'button',
            'parent_id' => $container->id,
            'sort' => 1,
            'data' => [
                'content' => ['label' => 'Get a Quote', 'link' => ['url' => '/contact']],
                'style' => ['background' => '#14324f', 'color' => '#ffffff'],
                'advanced' => ['padding' => [
                    'top' => ['value' => 12, 'unit' => 'px'],
                    'bottom' => ['value' => 12, 'unit' => 'px'],
                ]],
            ],
        ]);

        return $page;
    }

    public function test_container_renders_as_single_semantic_element_with_bare_children(): void
    {
        $result = app(PageRenderer::class)->render($this->makePage());
        $html = preg_replace('/\s+/', ' ', $result['html']);

        // One <section>, exactly one <h1> and one <a> — and no div soup.
        $this->assertStringContainsString('<section class="b', $html);
        $this->assertStringContainsString('>Roofing done right</h1>', $html);
        $this->assertStringContainsString('href="/contact"', $html);
        $this->assertSame(0, substr_count($html, '<div'), 'Lean-DOM contract broken: wrapper divs found');
    }

    public function test_style_compiler_emits_grid_tracks_and_responsive_blocks(): void
    {
        $css = app(PageRenderer::class)->render($this->makePage())['css'];

        $this->assertStringContainsString('grid-template-columns:33fr 67fr', $css);
        $this->assertStringContainsString('background:#f7f9fa', $css);
        $this->assertStringContainsString('font-size:44px', $css);
        $this->assertStringContainsString('@media(max-width:640px)', $css);
        $this->assertStringContainsString('font-size:30px', $css);
        $this->assertStringContainsString('grid-template-columns:1fr', $css); // mobile stack
        $this->assertStringContainsString('padding-top:12px', $css);
    }

    public function test_dynamic_tags_resolve_inside_rendered_content(): void
    {
        \Buildr\Models\SiteSetting::set('phone', '(410) 555-0114');

        $page = Page::create(['title' => 'Contact', 'slug' => 'contact']);
        $page->nodes()->create([
            'type' => 'heading',
            'sort' => 0,
            'data' => ['content' => ['text' => 'Call {{site.phone}}', 'tag' => 'h2']],
        ]);

        $html = app(PageRenderer::class)->render($page)['html'];

        $this->assertStringContainsString('Call (410) 555-0114', $html);
    }

    public function test_hidden_nodes_are_skipped(): void
    {
        $page = Page::create(['title' => 'T', 'slug' => 't']);
        $page->nodes()->create([
            'type' => 'heading', 'sort' => 0, 'visible' => false,
            'data' => ['content' => ['text' => 'Secret', 'tag' => 'h2']],
        ]);

        $this->assertStringNotContainsString('Secret', app(PageRenderer::class)->render($page)['html']);
    }
}
