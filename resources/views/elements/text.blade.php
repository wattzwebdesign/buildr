@php
    $classes = trim($node->cssId().' '.($node->setting('advanced', 'css_class') ?? ''));
@endphp
<div class="{{ $classes }}">{!! $body !!}</div>
