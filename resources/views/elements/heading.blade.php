@php
    $htmlTag = $tag ?? 'h2';
    $classes = trim($node->cssId().' '.($node->setting('advanced', 'css_class') ?? ''));
    $anchor = $node->setting('advanced', 'anchor_id');
    $url = $link['url'] ?? null;
@endphp
<{{ $htmlTag }} class="{{ $classes }}"@if($anchor) id="{{ $anchor }}"@endif>@if($url)<a href="{{ $url }}"@if($link['new_tab'] ?? false) target="_blank" rel="noopener"@endif>{{ $text }}</a>@else{{ $text }}@endif</{{ $htmlTag }}>
