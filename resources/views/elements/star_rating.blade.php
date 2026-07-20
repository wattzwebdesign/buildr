@php
    $classes = trim($node->cssId().' b-stars '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
    $value = max(0, min(5, (float) ($rating ?? 5)));
    $full = '<svg viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg>';
    $half = '<svg viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><defs><clipPath id="h-'.$node->id.'"><rect x="0" y="0" width="12" height="24"/></clipPath></defs><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z" clip-path="url(#h-'.$node->id.')"/></svg>';
@endphp
<div class="{{ $classes }}" role="img" aria-label="Rated {{ $value }} out of 5">
@for ($i = 1; $i <= 5; $i++)
@if ($value >= $i){!! $full !!}
@elseif ($value >= $i - .5)<span class="st-e" style="position:relative;display:inline-flex"><span style="position:absolute;inset:0;color:inherit">{!! $full !!}</span><span style="position:relative;color:{{ $node->setting('style', 'color') ?: '#f59e0b' }}">{!! $half !!}</span></span>
@else<span class="st-e">{!! $full !!}</span>
@endif
@endfor
</div>
