<hr class="{{ trim($node->cssId().' '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? '')) }}">
