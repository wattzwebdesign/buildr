@php
    $htmlTag = $tag ?? 'div';
    $classes = trim($node->cssId().' '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
    $anchor = $renderer->tags()->resolve($node->setting('advanced', 'anchor_id'), ['page' => $node->page]);
    $cols = count($widths ?? [100]);
    $columns = $renderer->renderContainerColumns($node, $cols);
    $editor = $renderer->isEditor();
    // flex alignment rules target the .bcol wrapper — keep it for single
    // children when any stack alignment is set so public matches the editor
    $hasStackStyles = ! empty($col_halign) || ! empty($col_valign);
@endphp
<{{ $htmlTag }} class="{{ $classes }}"@if($anchor) id="{{ $anchor }}"@endif>
@foreach ($columns as $ci => $column)
@if ($column['sole_container'])
{!! $column['html'] !!}
@elseif ($editor)
<div class="bcol" data-bcol="{{ $node->id }}:{{ $ci }}">{!! $column['html'] !!}<div class="bcol-ph {{ $column['count'] ? 'mini' : '' }}" data-bcolph="{{ $node->id }}:{{ $ci }}"><span>{{ $column['count'] ? '+' : '+ Drop an element here' }}</span></div></div>
@elseif ($column['count'] > 1 || ($column['count'] === 1 && $hasStackStyles))
<div class="bcol">{!! $column['html'] !!}</div>
@elseif ($column['count'] === 1)
{!! $column['html'] !!}
@else
<div class="bcol"></div>
@endif
@endforeach
</{{ $htmlTag }}>
