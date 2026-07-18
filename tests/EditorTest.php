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

    public function test_selecting_a_node_loads_its_fields(): void
    {
        $page = $this->pageWithHero();
        $heading = $page->nodes()->where('type', 'heading')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $heading->id)
            ->assertSet('content.text', 'Original headline')
            ->assertSee('Heading');
    }

    public function test_editing_a_text_field_persists_and_rerenders(): void
    {
        $page = $this->pageWithHero();
        $heading = $page->nodes()->where('type', 'heading')->first();

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $heading->id)
            ->set('content.text', 'New headline')
            ->assertSee('New headline');

        $this->assertSame('New headline', $heading->fresh()->setting('content', 'text'));
    }

    public function test_publish_from_editor(): void
    {
        $page = $this->pageWithHero();
        $page->update(['published_at' => null]);

        Livewire::test(Editor::class, ['page' => $page])->call('publish');

        $this->assertTrue($page->fresh()->isPublished());
    }
}
