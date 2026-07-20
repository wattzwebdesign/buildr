@php
    $classes = trim($node->cssId().' b-tabs '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
    $group = 'tb-'.$node->id;
@endphp
<div class="{{ $classes }}" style="position:relative">
@foreach (($tabs ?? []) as $i => $tab)
<input class="tb-radio" type="radio" name="{{ $group }}" id="{{ $group }}-{{ $i }}"@if($i === 0) checked @endif>
@endforeach
<div class="tb-labels">
@foreach (($tabs ?? []) as $i => $tab)
<label for="{{ $group }}-{{ $i }}">{{ $tab['label'] ?? '' }}</label>
@endforeach
</div>
<div class="tb-panels">
@foreach (($tabs ?? []) as $tab)
<div class="tb-panel">{!! \Buildr\Support\Richtext::render($tab['body'] ?? '') !!}</div>
@endforeach
</div>
</div>
