@php
    $classes = trim($node->cssId().' '.($node->setting('advanced', 'css_class') ?? ''));
@endphp
<a class="{{ $classes }}" href="{{ $link['url'] ?? '#' }}"@if($link['new_tab'] ?? false) target="_blank" rel="noopener"@endif>{{ $label }}</a>
