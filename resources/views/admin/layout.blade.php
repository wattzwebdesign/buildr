<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'Buildr' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ route('buildr.admin.css') }}?v={{ substr(\Buildr\Support\UpdateCheck::installedRef() ?? '0', 0, 12) }}">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path fill='%23ffb200' d='M13 2 4.5 13.5H11L9.5 22 19 10h-6.5z'/></svg>">
<script>document.documentElement.dataset.theme = localStorage.getItem('buildr-theme') || 'dark';</script>
<style>body{overflow:auto}</style>
</head>
<body>
{{ $slot }}
<script>
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    const t = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = t;
    localStorage.setItem('buildr-theme', t);
});
</script>
</body>
</html>
