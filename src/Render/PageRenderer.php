<?php

namespace Buildr\Render;

use Buildr\DynamicTags\TagRegistry;
use Buildr\Models\Page;
use Buildr\Models\PageNode;
use Buildr\Support\ElementRegistry;
use Illuminate\Support\Facades\Cache;

class PageRenderer
{
    public function __construct(
        private ElementRegistry $elements,
        private TagRegistry $tags,
    ) {
    }

    public function tags(): TagRegistry
    {
        return $this->tags;
    }

    /**
     * @return array{html: string, css: string}
     */
    public function render(Page $page): array
    {
        $page->load(['nodes' => fn ($q) => $q->where('visible', true)->where('is_draft', false)]);

        $compiler = new StyleCompiler(config('buildr.breakpoints', ['tablet' => 1024, 'mobile' => 640]));

        $roots = $page->nodes->whereNull('parent_id')->sortBy('sort');
        $html = '';

        foreach ($roots as $node) {
            $html .= $this->renderNode($node, $compiler);
        }

        return ['html' => $html, 'css' => $compiler->compile()];
    }

    /** Cached render keyed by page + last update; publish busts it naturally. */
    public function renderCached(Page $page): array
    {
        $key = "buildr.page.{$page->id}.".($page->updated_at?->timestamp ?? 0);

        return Cache::remember($key, now()->addWeek(), fn () => $this->render($page));
    }

    /**
     * Editor-mode render: each root section separately (so the canvas can
     * wrap them in selection chrome) plus the compiled CSS for the page.
     *
     * @return array{roots: array<int, array{id: int, type: string, label: string, html: string}>, css: string}
     */
    /** When true, rendered nodes are tagged with data-bnode for canvas selection. */
    private bool $editorMode = false;

    public function renderEditor(Page $page): array
    {
        $this->editorMode = true;
        $page->load(['nodes' => fn ($q) => $q->where('is_draft', true)]);

        $compiler = new StyleCompiler(config('buildr.breakpoints', ['tablet' => 1024, 'mobile' => 640]));

        $roots = [];
        foreach ($page->nodes->whereNull('parent_id')->sortBy('sort') as $node) {
            $roots[] = [
                'id' => $node->id,
                'type' => $node->type,
                'label' => $node->data['content']['_label'] ?? $this->elements->get($node->type)::label(),
                'html' => $this->renderNode($node, $compiler),
            ];
        }

        return ['roots' => $roots, 'css' => $compiler->compile()];
    }

    /**
     * Partition a container's children into its columns. Children carry a
     * `_col` index in content; legacy children without one flow in order
     * (index % columns), which matches the old auto-placement exactly.
     *
     * @return array<int, array{html: string, count: int}>
     */
    public function renderContainerColumns(PageNode $node, int $cols): array
    {
        $cols = max(1, $cols);
        $columns = array_fill(0, $cols, ['html' => '', 'count' => 0, 'sole_container' => false]);

        $children = $node->children()
            ->when(! $this->editorMode, fn ($q) => $q->where('visible', true))
            ->get()->values();

        foreach ($children as $i => $child) {
            $col = min((int) ($child->data['content']['_col'] ?? ($i % $cols)), $cols - 1);
            $columns[$col]['html'] .= $this->renderNode($child, $this->activeCompiler);
            $columns[$col]['count']++;
            $columns[$col]['sole_container'] = $columns[$col]['count'] === 1 && $child->type === 'container';
        }

        return $columns;
    }

    public function renderChildren(PageNode $parent): string
    {
        $html = '';

        $children = $parent->children()
            ->when(! $this->editorMode, fn ($q) => $q->where('visible', true))
            ->get();

        foreach ($children as $child) {
            $html .= $this->renderNode($child, $this->activeCompiler);
        }

        return $html;
    }

    private ?StyleCompiler $activeCompiler = null;

    private function renderNode(PageNode $node, StyleCompiler $compiler): string
    {
        $class = $this->elements->get($node->type);
        $element = new $class($node);

        $compiler->addNode($node, $element);

        $previous = $this->activeCompiler;
        $this->activeCompiler = $compiler;

        try {
            $html = $element->render($this);
        } finally {
            $this->activeCompiler = $previous;
        }

        if ($this->editorMode) {
            $attrs = 'data-bnode="'.$node->id.'"';
            $attrs .= ' data-blabel="'.e($node->data['content']['_label'] ?? $this->elements->get($node->type)::label()).'"';
            if ($node->type === 'container') {
                $attrs .= ' data-bcontainer="'.$node->id.'"';
            }
            if ($node->parent_id) {
                $attrs .= ' draggable="true"';
            }
            if (! $node->visible) {
                $attrs .= ' data-bhidden';
            }
            $html = preg_replace('/<([a-zA-Z][a-zA-Z0-9-]*)/', '<$1 '.$attrs.' ', $html, 1);
        }

        return $html;
    }

    public function isEditor(): bool
    {
        return $this->editorMode;
    }
}
