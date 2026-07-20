@php
    $classes = trim($node->cssId().' b-gallery '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
@endphp
<div class="{{ $classes }}">
@foreach (($images ?? []) as $img)
<img src="{{ $img['src'] ?? '/buildr-assets/placeholder.svg' }}" alt="{{ $img['alt'] ?? '' }}" loading="lazy" decoding="async">
@endforeach
</div>
