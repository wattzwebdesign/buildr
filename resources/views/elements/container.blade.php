@php
    $htmlTag = $tag ?? 'div';
    $classes = trim($node->cssId().' '.($node->setting('advanced', 'css_class') ?? ''));
    $anchor = $node->setting('advanced', 'anchor_id');
@endphp
<{{ $htmlTag }} class="{{ $classes }}"@if($anchor) id="{{ $anchor }}"@endif>
{!! $renderer->renderChildren($node) !!}
@if ($renderer->isEditor())
@php
    $cols = count($widths ?? [100]);
    $placeholders = max(0, $cols - $node->children()->count());
@endphp
@for ($i = 0; $i < $placeholders; $i++)
<div class="bcol-ph" data-bcolph="{{ $node->id }}"><span>+ Drop an element here</span></div>
@endfor
@endif
</{{ $htmlTag }}>
