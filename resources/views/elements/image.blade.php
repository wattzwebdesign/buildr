@php
    $classes = trim($node->cssId().' '.($node->setting('advanced', 'css_class') ?? ''));
    $url = $link['url'] ?? null;
    $img = '<img class="'.e($classes).'" src="'.e($src).'" alt="'.e($alt ?? '').'" loading="lazy" decoding="async">';
    if ($url) {
        $img = '<a href="'.e($url).'">'.$img.'</a>';
    }
@endphp
@if(!empty($caption))
<figure class="{{ $classes }}-fig">{!! $img !!}<figcaption>{{ $caption }}</figcaption></figure>
@else
{!! $img !!}
@endif
