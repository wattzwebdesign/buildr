<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $page->seo_title ?? $page->title }}</title>
@if($page->seo_description)<meta name="description" content="{{ $page->seo_description }}">@endif
<style>*,*::before,*::after{box-sizing:border-box}body{margin:0}img{max-width:100%;height:auto;display:block}{!! $css !!}</style>
</head>
<body>
{!! $html !!}
</body>
</html>
