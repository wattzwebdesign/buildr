<?php

namespace Buildr\Tests;

use Buildr\Http\Livewire\Editor;
use Buildr\Models\Page;
use Livewire\Livewire;

class StyleTabReproTest extends TestCase
{
    public function test_style_tab_survives_malformed_legacy_data(): void
    {
        $page = Page::create(['title' => 'T', 'slug' => 't']);

        $container = $page->nodes()->create([
            'type' => 'container', 'sort' => 0,
            'data' => ['content' => ['widths' => [100]]],
        ]);
        // legacy/corrupt shapes: arrays where scalars belong
        $heading = $page->nodes()->create([
            'type' => 'heading', 'parent_id' => $container->id, 'sort' => 0,
            'data' => [
                'content' => ['text' => 'Hi', 'tag' => 'h2'],
                'style' => [
                    'text_align' => ['desktop' => ['weird' => 'nested']],
                    'font_weight' => ['oops'],
                    'color' => ['not-a-color'],
                ],
            ],
        ]);

        Livewire::test(Editor::class, ['page' => $page])
            ->call('selectNode', $heading->id)
            ->call('setTab', 'style')
            ->assertOk();
    }

    public function test_style_and_advanced_tabs_render_for_every_element(): void
    {
        $page = Page::create(['title' => 'T', 'slug' => 't']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1);
        $container = $page->rootNodes()->first();

        foreach (['heading', 'text', 'image', 'button', 'divider', 'spacer'] as $type) {
            $component->call('dropInto', $type, $container->id, 0);
        }

        foreach ($page->nodes()->get() as $node) {
            $component->call('selectNode', $node->id)
                ->call('setTab', 'style')
                ->assertOk()
                ->call('setTab', 'advanced')
                ->assertOk();
        }
    }
}
