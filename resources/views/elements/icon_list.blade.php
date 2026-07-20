@php
    $classes = trim($node->cssId().' b-iconlist '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
@endphp
<ul class="{{ $classes }}">
@foreach (($items ?? []) as $item)
<li>{!! \Buildr\Support\Icons::svg($item['icon'] ?? 'check') !!}@if(!empty($item['url']))<a href="{{ $item['url'] }}">{{ $item['text'] ?? '' }}</a>@else<span>{{ $item['text'] ?? '' }}</span>@endif</li>
@endforeach
</ul>
