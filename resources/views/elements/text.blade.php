@php
    $classes = trim($node->cssId().' b-text '.($node->setting('advanced', 'css_class') ?? ''));
@endphp
<div class="{{ $classes }}">{!! $body !!}</div>
