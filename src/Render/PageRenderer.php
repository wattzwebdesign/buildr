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
        $page->load(['nodes' => fn ($q) => $q->where('visible', true)]);

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

    public function renderChildren(PageNode $parent): string
    {
        $html = '';

        foreach ($parent->children()->where('visible', true)->get() as $child) {
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
            return $element->render($this);
        } finally {
            $this->activeCompiler = $previous;
        }
    }
}
