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
            ->assertSee('Original headline')
            ->assertSee('Editing · Home');
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

        $css = app(\Buildr\Render\PageRenderer::class)->render($page->fresh())['css'];

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

        $css = app(\Buildr\Render\PageRenderer::class)->render($page->fresh())['css'];

        $this->assertStringContainsString('padding-top:2em', $css);
        $this->assertStringContainsString('padding-bottom:3em', $css);
    }

    public function test_container_width_presets(): void
    {
        $page = $this->pageWithHero();
        $container = $page->nodes()->where('type', 'container')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $container->id)
            ->call('setWidths', '33,67');

        $css = app(\Buildr\Render\PageRenderer::class)->render($page->fresh())['css'];

        $this->assertStringContainsString('grid-template-columns:33fr 67fr', $css);
    }

    public function test_publish_from_editor(): void
    {
        $page = $this->pageWithHero();
        $page->update(['published_at' => null]);

        Livewire::test(Editor::class, ['page' => $page])->call('publish');

        $this->assertTrue($page->fresh()->isPublished());
    }
}
