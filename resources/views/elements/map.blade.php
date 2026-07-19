@php
    $classes = trim($node->cssId().' b-map '.($node->setting('advanced', 'css_class') ?? ''));
@endphp
<div class="{{ $classes }}">
<iframe src="https://www.google.com/maps?q={{ urlencode($address ?? '') }}&z={{ (int) ($zoom ?? 14) }}&output=embed" title="Map of {{ $address }}" loading="lazy"></iframe>
</div>
