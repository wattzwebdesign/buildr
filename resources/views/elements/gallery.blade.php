@php
    $classes = trim($node->cssId().' b-gallery '.($node->setting('advanced', 'css_class') ?? ''));
@endphp
<div class="{{ $classes }}">
@foreach (($images ?? []) as $img)
<img src="{{ $img['src'] ?? '/buildr-assets/placeholder.svg' }}" alt="{{ $img['alt'] ?? '' }}" loading="lazy" decoding="async">
@endforeach
</div>
