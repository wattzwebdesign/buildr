@php
    $classes = trim($node->cssId().' b-button '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
@endphp
<a class="{{ $classes }}" href="{{ $link['url'] ?? '#' }}"@if($link['new_tab'] ?? false) target="_blank" rel="noopener"@endif>{{ $label }}</a>
