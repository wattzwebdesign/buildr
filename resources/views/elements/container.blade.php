@php
    $htmlTag = $tag ?? 'div';
    $classes = trim($node->cssId().' '.($node->setting('advanced', 'css_class') ?? ''));
    $anchor = $node->setting('advanced', 'anchor_id');
@endphp
<{{ $htmlTag }} class="{{ $classes }}"@if($anchor) id="{{ $anchor }}"@endif>
{!! $renderer->renderChildren($node) !!}
</{{ $htmlTag }}>
