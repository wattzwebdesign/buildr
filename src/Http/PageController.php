<?php

namespace Buildr\Http;

use Buildr\Models\Page;
use Buildr\Render\PageRenderer;

class PageController
{
    public function __invoke(PageRenderer $renderer, string $slug = 'home')
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        $rendered = $renderer->renderCached($page);

        return view('buildr::page', [
            'page' => $page,
            'html' => $rendered['html'],
            'css' => $rendered['css'],
        ]);
    }
}
