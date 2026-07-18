@php
    $htmlTag = $tag ?? 'div';
    $classes = trim($node->cssId().' '.($node->setting('advanced', 'css_class') ?? ''));
    $anchor = $node->setting('advanced', 'anchor_id');
    $cols = count($widths ?? [100]);
    $columns = $renderer->renderContainerColumns($node, $cols);
    $editor = $renderer->isEditor();
@endphp
<{{ $htmlTag }} class="{{ $classes }}"@if($anchor) id="{{ $anchor }}"@endif>
@foreach ($columns as $ci => $column)
@if ($editor)
<div class="bcol" data-bcol="{{ $node->id }}:{{ $ci }}">{!! $column['html'] !!}<div class="bcol-ph {{ $column['count'] ? 'mini' : '' }}" data-bcolph="{{ $node->id }}:{{ $ci }}"><span>{{ $column['count'] ? '+' : '+ Drop an element here' }}</span></div></div>
@elseif ($column['count'] > 1)
<div class="bcol">{!! $column['html'] !!}</div>
@elseif ($column['count'] === 1)
{!! $column['html'] !!}
@else
<div class="bcol"></div>
@endif
@endforeach
</{{ $htmlTag }}>
