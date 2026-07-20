@php
    $classes = trim($node->cssId().' b-social '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
@endphp
<div class="{{ $classes }}">
@foreach (($accounts ?? []) as $account)
<a href="{{ $account['url'] ?? '#' }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($account['platform'] ?? '') }}">{!! \Buildr\Support\Icons::svg($account['platform'] ?? 'globe') !!}</a>
@endforeach
</div>
