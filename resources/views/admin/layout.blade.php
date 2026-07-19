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
<div id="bcf" class="bcf-overlay" style="display:none">
  <div class="bcf-modal">
    <div class="bcf-msg" id="bcf-msg"></div>
    <div class="bcf-actions">
      <button type="button" class="bcf-btn" id="bcf-cancel">Cancel</button>
      <button type="button" class="bcf-btn ok" id="bcf-ok">OK</button>
    </div>
  </div>
</div>
<script>
window.bConfirm = (msg, opts = {}) => new Promise(resolve => {
  const overlay = document.getElementById('bcf');
  const okBtn = document.getElementById('bcf-ok');
  const cancelBtn = document.getElementById('bcf-cancel');
  document.getElementById('bcf-msg').textContent = msg;
  okBtn.textContent = opts.okText || (opts.danger ? 'Delete' : 'OK');
  okBtn.classList.toggle('danger', !!opts.danger);
  overlay.style.display = 'flex';

  const done = v => {
    overlay.style.display = 'none';
    okBtn.removeEventListener('click', onOk);
    cancelBtn.removeEventListener('click', onCancel);
    document.removeEventListener('keydown', onKey);
    overlay.removeEventListener('mousedown', onBack);
    resolve(v);
  };
  const onOk = () => done(true);
  const onCancel = () => done(false);
  const onKey = e => { if (e.key === 'Escape') onCancel(); if (e.key === 'Enter') onOk(); };
  const onBack = e => { if (e.target === overlay) onCancel(); };

  okBtn.addEventListener('click', onOk);
  cancelBtn.addEventListener('click', onCancel);
  document.addEventListener('keydown', onKey);
  overlay.addEventListener('mousedown', onBack);
  okBtn.focus();
});

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
