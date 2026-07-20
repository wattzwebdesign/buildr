<div class="{{ trim($node->cssId().' b-html '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? '')) }}">{!! $code ?? '' !!}</div>
