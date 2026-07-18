<?php

namespace Buildr\Tests;

use Buildr\Http\Livewire\Editor;
use Buildr\Models\Page;
use Buildr\Render\PageRenderer;
use Livewire\Livewire;

class EditorTreeTest extends TestCase
{
    public function test_add_container_and_element_from_library(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 2);

        $container = $page->nodes()->whereNull('parent_id')->first();
        $this->assertSame([50, 50], $container->setting('content', 'widths'));

        // container is auto-selected, so the element drops inside it
        $component->call('addElement', 'heading');

        $heading = $page->nodes()->where('type', 'heading')->first();
        $this->assertSame($container->id, $heading->parent_id);
        $this->assertSame('Heading', $heading->setting('content', 'text'));
    }

    public function test_element_added_after_selected_sibling(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addElement', 'heading')   // child 0, now selected
            ->call('addElement', 'button');   // sibling after the heading

        $types = $page->nodes()->whereNotNull('parent_id')->orderBy('sort')->pluck('type')->all();
        $this->assertSame(['heading', 'button'], $types);
    }

    public function test_insert_container_at_start_and_between(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addContainer', 2);

        $first = $page->rootNodes()->orderBy('sort')->first();

        // "+" gap at very top of the page
        $component->call('openLibrary', 0)->call('addContainer', 3);

        $widthCounts = $page->rootNodes()->orderBy('sort')->get()
            ->map(fn ($n) => count($n->setting('content', 'widths')))->all();

        $this->assertSame([3, 1, 2], $widthCounts);
    }

    public function test_move_duplicate_and_delete_sections(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addContainer', 2);

        [$a, $b] = $page->rootNodes()->orderBy('sort')->pluck('id')->all();

        $component->call('moveNode', $b, 'up');
        $this->assertSame([$b, $a], $page->rootNodes()->orderBy('sort')->pluck('id')->all());

        // duplicate a section with a child: deep copy
        $component->call('selectNode', $a)->call('addElement', 'heading');
        $component->call('duplicateNode', $a);

        $this->assertSame(3, $page->rootNodes()->count());
        $copy = $page->rootNodes()->orderBy('sort')->get()->last();
        $this->assertSame(1, $copy->children()->count());

        // delete removes the section and its children
        $component->call('deleteNode', $a);
        $this->assertSame(2, $page->rootNodes()->count());
        $this->assertSame(1, $page->nodes()->whereNotNull('parent_id')->count());
    }

    public function test_visibility_toggle_hides_from_public_render(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new', 'published_at' => now()]);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addElement', 'heading');

        $heading = $page->nodes()->where('type', 'heading')->first();
        $component->call('toggleVisible', $heading->id);

        $public = app(PageRenderer::class)->render($page->fresh())['html'];
        $this->assertStringNotContainsString('<h2', $public);

        // still present (dimmed) in the editor render
        $editor = app(PageRenderer::class)->renderEditor($page->fresh());
        $html = collect($editor['roots'])->pluck('html')->implode('');
        $this->assertStringContainsString('data-bhidden', $html);
    }

    public function test_editor_render_tags_children_for_selection(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addElement', 'heading');

        $editor = app(PageRenderer::class)->renderEditor($page->fresh());
        $html = collect($editor['roots'])->pluck('html')->implode('');

        $heading = $page->nodes()->where('type', 'heading')->first();
        $this->assertStringContainsString('data-bnode="'.$heading->id.'"', $html);
    }
}
