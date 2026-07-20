@php
    $classes = trim($node->cssId().' b-text '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
@endphp
<div class="{{ $classes }}">{!! \Buildr\Support\Richtext::render($body) !!}</div>
