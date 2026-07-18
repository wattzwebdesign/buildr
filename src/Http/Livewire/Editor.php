<?php

namespace Buildr\Http\Livewire;

use Buildr\Models\Page;
use Buildr\Models\PageNode;
use Buildr\Render\PageRenderer;
use Buildr\Support\ElementRegistry;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('buildr::admin.layout')]
class Editor extends Component
{
    public Page $page;

    public ?int $selectedId = null;

    public string $tab = 'content';

    /** Scalar content settings of the selected node, bound to panel inputs. */
    public array $content = [];

    public function mount(Page $page): void
    {
        $this->page = $page;

        $first = $page->rootNodes()->first();
        if ($first) {
            $this->selectNode($first->id);
        }
    }

    public function selectNode(int $id): void
    {
        $this->selectedId = $id;
        $this->tab = 'content';
        $this->loadContent();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['content', 'style', 'advanced'], true)) {
            $this->tab = $tab;
        }
    }

    public function publish(): void
    {
        $this->page->update(['published_at' => now()]);
    }

    /** Persist a panel edit into the node's data JSON and refresh the canvas. */
    public function updatedContent(mixed $value, string $key): void
    {
        $node = $this->node();
        if (! $node) {
            return;
        }

        $data = $node->data ?? [];
        $data['content'][$key] = $value;
        $node->update(['data' => $data]);
        $this->page->touch();
    }

    private function node(): ?PageNode
    {
        return $this->selectedId
            ? $this->page->nodes()->whereKey($this->selectedId)->first()
            : null;
    }

    private function loadContent(): void
    {
        $this->content = [];
        $node = $this->node();
        if (! $node) {
            return;
        }

        $stored = $node->settings('content');
        foreach ($this->elementClass($node)::contentFields() as $field) {
            $value = $stored[$field->key] ?? $field->default;
            if (is_scalar($value) || $value === null) {
                $this->content[$field->key] = $value;
            }
        }
    }

    private function elementClass(PageNode $node): string
    {
        return app(ElementRegistry::class)->get($node->type);
    }

    public function render()
    {
        $rendered = app(PageRenderer::class)->renderEditor($this->page);

        $node = $this->node();
        $schema = $node ? $this->elementClass($node)::schema() : null;

        return view('buildr::livewire.editor', [
            'rendered' => $rendered,
            'schema' => $schema,
            'fields' => $schema ? $schema['tabs'][$this->tab] : [],
        ])->title("Buildr — {$this->page->title}");
    }
}
