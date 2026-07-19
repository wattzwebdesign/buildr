@php
    $classes = trim($node->cssId().' b-iconlist '.($node->setting('advanced', 'css_class') ?? ''));
@endphp
<ul class="{{ $classes }}">
@foreach (($items ?? []) as $item)
<li>{!! \Buildr\Support\Icons::svg($item['icon'] ?? 'check') !!}@if(!empty($item['url']))<a href="{{ $item['url'] }}">{{ $item['text'] ?? '' }}</a>@else<span>{{ $item['text'] ?? '' }}</span>@endif</li>
@endforeach
</ul>
