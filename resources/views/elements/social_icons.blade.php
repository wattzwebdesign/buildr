@php
    $classes = trim($node->cssId().' b-social '.($node->setting('advanced', 'css_class') ?? ''));
@endphp
<div class="{{ $classes }}">
@foreach (($accounts ?? []) as $account)
<a href="{{ $account['url'] ?? '#' }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($account['platform'] ?? '') }}">{!! \Buildr\Support\Icons::svg($account['platform'] ?? 'globe') !!}</a>
@endforeach
</div>
