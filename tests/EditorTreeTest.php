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
            ->call('addContainer', 1);

        $first = $page->rootNodes()->orderBy('sort')->first();

        // page-level inserts go through the "+" gaps (openLibrary sets the spot)
        $component->call('openLibrary', $first->id)->call('addContainer', 2);
        $component->call('openLibrary', 0)->call('addContainer', 3);

        $widthCounts = $page->rootNodes()->orderBy('sort')->get()
            ->map(fn ($n) => count($n->setting('content', 'widths')))->all();

        $this->assertSame([3, 1, 2], $widthCounts);
    }

    public function test_move_duplicate_and_delete_sections(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1);
        $a = $page->rootNodes()->first()->id;
        $component->call('openLibrary', $a)->call('addContainer', 2);

        [$a, $b] = $page->rootNodes()->orderBy('sort')->pluck('id')->all();

        $component->call('moveNode', $b, 'up');
        $this->assertSame([$b, $a], $page->rootNodes()->orderBy('sort')->pluck('id')->all());

        // duplicate a section with a child: deep copy
        $component->call('selectNode', $a)->call('addElement', 'heading');
        $component->call('duplicateNode', $a);

        $this->assertSame(3, $page->rootNodes()->count());
        $copy = $page->rootNodes()->orderBy('sort')->get()->last();
        $this->assertSame(1, $copy->children()->count());

        // delete removes the section and its children; section b keeps its
        // 2 auto column containers, the surviving copy keeps its heading
        $component->call('deleteNode', $a);
        $this->assertSame(2, $page->rootNodes()->count());
        $this->assertSame(3, $page->nodes()->whereNotNull('parent_id')->count());
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

    public function test_empty_container_shows_column_placeholders_in_editor_only(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new', 'published_at' => now()]);

        Livewire::test(Editor::class, ['page' => $page])->call('addContainer', 2);

        $editorHtml = collect(app(PageRenderer::class)->renderEditor($page->fresh())['roots'])
            ->pluck('html')->implode('');
        $this->assertSame(2, substr_count($editorHtml, 'data-bcolph'));

        $publicHtml = app(PageRenderer::class)->render($page->fresh())['html'];
        $this->assertStringNotContainsString('bcol-ph', $publicHtml);
    }

    public function test_drop_element_lands_in_target_container(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1);
        $first = $page->rootNodes()->first();
        $component->call('openLibrary', $first->id)->call('addContainer', 2);

        // drop onto the FIRST container even though the second is selected
        $component->call('dropElement', 'button', $first->id);

        $button = $page->nodes()->where('type', 'button')->first();
        $this->assertSame($first->id, $button->parent_id);
    }

    public function test_container_selected_nests_new_container_inside(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)      // root, auto-selected
            ->call('addContainer', 2);     // nests inside it

        $root = $page->rootNodes()->first();
        $this->assertSame(1, $page->rootNodes()->count());

        $inner = $root->children()->first();
        $this->assertSame('container', $inner->type);
        $this->assertSame([50, 50], $inner->setting('content', 'widths'));
    }

    public function test_drop_container_card_nests_with_columns(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1);

        $root = $page->rootNodes()->first();
        $component->call('dropElement', 'container', $root->id, 3);

        $inner = $root->children()->first();
        $this->assertSame('container', $inner->type);
        $this->assertSame([33, 33, 33], $inner->setting('content', 'widths'));

        // root + nested parent + its 3 auto-created column containers
        $html = collect(app(PageRenderer::class)->renderEditor($page->fresh())['roots'])->pluck('html')->implode('');
        $this->assertSame(5, substr_count($html, 'data-bcontainer'));
    }

    public function test_multi_column_container_creates_inner_column_containers(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        Livewire::test(Editor::class, ['page' => $page])->call('addContainer', 2);

        $parent = $page->rootNodes()->first();
        $columns = $parent->children()->orderBy('sort')->get();

        $this->assertCount(2, $columns);
        $this->assertSame(['container', 'container'], $columns->pluck('type')->all());
        $this->assertSame([0, 1], $columns->map(fn ($c) => $c->data['content']['_col'])->all());

        // each column is its own selectable container in the editor…
        $html = collect(app(PageRenderer::class)->renderEditor($page->fresh())['roots'])->pluck('html')->implode('');
        $this->assertSame(3, substr_count($html, 'data-bcontainer'));
        // …rendered bare as the grid item — no .bcol wrapper around a column container
        $this->assertSame(2, substr_count($html, 'Drop an element here'));

        // only the ROOT container is boxed/centered — nested columns must
        // stretch to fill their grid track
        $css = app(PageRenderer::class)->render($page->fresh()->fill(['published_at' => now()]))['css'];
        $this->assertSame(1, substr_count($css, 'margin-inline:auto'));
    }

    public function test_multiple_elements_stack_in_one_column(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new', 'published_at' => now()]);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 2);

        $parent = $page->rootNodes()->first();
        [$left, $right] = $parent->children()->orderBy('sort')->get();

        // heading + text + button stacked in the LEFT column container; image right
        $component->call('dropInto', 'heading', $left->id, 0)
            ->call('dropInto', 'text', $left->id, 0)
            ->call('dropInto', 'button', $left->id, 0)
            ->call('dropInto', 'image', $right->id, 0);

        $this->assertSame(['heading', 'text', 'button'], $left->children()->orderBy('sort')->pluck('type')->all());
        $this->assertSame(['image'], $right->children()->pluck('type')->all());

        // public render: left column wraps its 3 elements in ONE flex div
        $html = app(PageRenderer::class)->render($page->fresh())['html'];
        $this->assertSame(1, substr_count($html, 'class="bcol"'));
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('<img', $html);
    }

    public function test_move_node_relative_reorders_within_column(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1);
        $container = $page->rootNodes()->first();

        $component->call('dropInto', 'heading', $container->id, 0)
            ->call('dropInto', 'text', $container->id, 0)
            ->call('dropInto', 'button', $container->id, 0);

        $button = $page->nodes()->where('type', 'button')->first();
        $heading = $page->nodes()->where('type', 'heading')->first();

        // drag the button above the heading
        $component->call('moveNodeRelative', $button->id, $heading->id, 'before');

        $types = $page->nodes()->whereNotNull('parent_id')->orderBy('sort')->pluck('type')->all();
        $this->assertSame(['button', 'heading', 'text'], $types);
    }

    public function test_move_node_to_another_containers_column(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 2);
        $first = $page->rootNodes()->first();
        $component->call('openLibrary', $first->id)->call('addContainer', 1);
        $second = $page->rootNodes()->orderBy('sort')->get()->last();

        $component->call('dropInto', 'heading', $first->id, 0);
        $heading = $page->nodes()->where('type', 'heading')->first();

        $component->call('moveNodeToColumn', $heading->id, $second->id, 0);

        $this->assertSame($second->id, $heading->fresh()->parent_id);
        $this->assertSame(0, $heading->fresh()->data['content']['_col']);
    }

    public function test_container_cannot_be_moved_into_its_own_descendant(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)     // root, selected
            ->call('addContainer', 1);    // nested inside root

        $root = $page->rootNodes()->first();
        $inner = $root->children()->first();

        $component->call('moveNodeToColumn', $root->id, $inner->id, 0);

        $this->assertNull($root->fresh()->parent_id); // unchanged
    }

    public function test_new_containers_default_to_10px_padding(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new', 'published_at' => now()]);

        Livewire::test(Editor::class, ['page' => $page])->call('addContainer', 1);

        $css = app(PageRenderer::class)->render($page->fresh())['css'];
        $this->assertStringContainsString('padding-top:10px', $css);
        $this->assertStringContainsString('padding-left:10px', $css);
    }

    public function test_editing_a_field_does_not_lose_column_assignment(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 2);
        $container = $page->rootNodes()->first();

        // heading + button both in the LEFT column
        $component->call('dropInto', 'heading', $container->id, 0)
            ->call('dropInto', 'button', $container->id, 0);

        $button = $page->nodes()->where('type', 'button')->first();
        $this->assertSame(0, $button->data['content']['_col']);

        // typing new button text must not move it to the right column
        $component->call('selectNode', $button->id)
            ->set('settings.content.label', 'New label');

        $fresh = $button->fresh();
        $this->assertSame('New label', $fresh->setting('content', 'label'));
        $this->assertSame(0, $fresh->data['content']['_col']);

        // style/advanced saves must preserve it too
        $component->call('setTab', 'style')->set('settings.style.background', '#111111');
        $this->assertSame(0, $button->fresh()->data['content']['_col']);
    }

    public function test_flex_alignment_controls_compile(): void
    {
        $page = Page::create(['title' => 'New', 'slug' => 'new', 'published_at' => now()]);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 2);
        $container = $page->rootNodes()->first();

        $component->call('dropInto', 'heading', $container->id, 0)
            ->call('selectNode', $container->id)
            ->set('settings.content.col_halign', 'center')
            ->set('settings.content.col_valign', 'space-between')
            ->set('settings.content.element_gap.value', 20);

        $result = app(PageRenderer::class)->render($page->fresh());

        $this->assertStringContainsString('> .bcol{align-items:center;justify-content:space-between;gap:20px;}', $result['css']);
        // single-child column keeps its wrapper so the flex rules apply
        $this->assertStringContainsString('class="bcol"', $result['html']);

        // element-level align-self
        $heading = $page->nodes()->where('type', 'heading')->first();
        Livewire::test(Editor::class, ['page' => $page->fresh()])
            ->call('selectNode', $heading->id)
            ->set('settings.advanced.align.desktop', 'center');

        $css = app(PageRenderer::class)->render($page->fresh())['css'];
        $this->assertStringContainsString('align-self:center', $css);
        $this->assertStringContainsString('justify-self:center', $css);
    }

    public function test_legacy_bucket_columns_upgrade_to_column_containers_on_mount(): void
    {
        $page = Page::create(['title' => 'Legacy', 'slug' => 'legacy']);

        // pre-column-model structure: elements directly inside a 2-col container
        $container = $page->nodes()->create([
            'type' => 'container', 'sort' => 0,
            'data' => ['content' => ['widths' => [50, 50]]],
        ]);
        $page->nodes()->create(['type' => 'heading', 'parent_id' => $container->id, 'sort' => 0,
            'data' => ['content' => ['text' => 'Hi', 'tag' => 'h2', '_col' => 0]]]);
        $page->nodes()->create(['type' => 'button', 'parent_id' => $container->id, 'sort' => 1,
            'data' => ['content' => ['label' => 'Go', '_col' => 0]]]);
        $page->nodes()->create(['type' => 'image', 'parent_id' => $container->id, 'sort' => 2,
            'data' => ['content' => ['src' => 'x.jpg', '_col' => 1]]]);

        Livewire::test(Editor::class, ['page' => $page]); // mount runs the upgrade

        $columns = $container->fresh()->children()->orderBy('sort')->get();
        $this->assertSame(['container', 'container'], $columns->pluck('type')->all());
        $this->assertSame(['heading', 'button'], $columns[0]->children()->orderBy('sort')->pluck('type')->all());
        $this->assertSame(['image'], $columns[1]->children()->pluck('type')->all());

        // idempotent: mounting again changes nothing
        Livewire::test(Editor::class, ['page' => $page->fresh()]);
        $this->assertSame(2, $container->fresh()->children()->count());
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
