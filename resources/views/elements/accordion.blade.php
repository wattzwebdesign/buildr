@php
    $classes = trim($node->cssId().' b-accordion '.($node->setting('advanced', 'css_class') ?? ''));
    $group = 'acc-'.$node->id;
@endphp
<div class="{{ $classes }}">
@foreach (($items ?? []) as $i => $item)
@php
    $dattrs = ($exclusive ?? true) ? ' name="'.$group.'"' : '';
    if ($i === 0 && ($first_open ?? true)) $dattrs .= ' open';
@endphp
<details{!! $dattrs !!}>
<summary>{{ $item['title'] ?? '' }}</summary>
<div class="acc-body">{!! \Buildr\Support\Richtext::render($item['body'] ?? '') !!}</div>
</details>
@endforeach
</div>
