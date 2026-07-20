@php
    $classes = trim($node->cssId().' b-video '.($renderer->tags()->resolve($node->setting('advanced', 'css_class'), ['page' => $node->page]) ?? ''));
    $embed = null;
    if ($url) {
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)~', $url, $m)) {
            $embed = 'https://www.youtube-nocookie.com/embed/'.$m[1].(($autoplay ?? false) ? '?autoplay=1&mute=1' : '');
        } elseif (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            $embed = 'https://player.vimeo.com/video/'.$m[1];
        }
    }
@endphp
<div class="{{ $classes }}">
@if ($embed)
<iframe src="{{ $embed }}" title="Video" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
@elseif ($url)
@php
    $attrs = '';
    if (!empty($poster)) $attrs .= ' poster="'.e($poster).'"';
    if ($controls ?? true) $attrs .= ' controls';
    if ($autoplay ?? false) $attrs .= ' autoplay muted playsinline';
    if ($loop ?? false) $attrs .= ' loop';
@endphp
<video src="{{ $url }}"{!! $attrs !!}></video>
@else
<img src="/buildr-assets/placeholder.svg" alt="Video placeholder">
@endif
</div>
