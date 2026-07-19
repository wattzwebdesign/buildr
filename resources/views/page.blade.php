<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $page->seo_title ?? $page->title }}</title>
@if($page->seo_description)<meta name="description" content="{{ $page->seo_description }}">@endif
<style>{!! \Buildr\Render\BaseCss::css() !!}{!! $css !!}</style>
</head>
<body class="buildr-page">
{!! $html !!}
</body>
</html>
