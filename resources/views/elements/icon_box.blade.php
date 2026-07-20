@php
    $classes = trim($node->cssId().' b-iconbox '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
    $url = $link['url'] ?? null;
@endphp
<div class="{{ $classes }}">
<span class="ib-icon">{!! \Buildr\Support\Icons::svg($icon ?? 'star') !!}</span>
<div class="ib-copy"><h3>@if($url)<a href="{{ $url }}">{{ $heading }}</a>@else{{ $heading }}@endif</h3>
<div class="ib-body">{!! \Buildr\Support\Richtext::render($body) !!}</div></div>
</div>
