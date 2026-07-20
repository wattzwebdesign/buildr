{{-- $active: 'pages' | 'media', $updateAvailable: bool --}}
<aside class="side">
  <div class="side-logo">
    <svg viewBox="0 0 24 24"><path d="M13 2 4.5 13.5H11L9.5 22 19 10h-6.5z"/></svg>
    Buildr
  </div>
  <div class="side-site">
    <span class="dotlive"></span> {{ \Buildr\Models\SiteSetting::get('name', config('app.name')) }}
  </div>
  <nav>
    <a href="{{ route('buildr.pages') }}" class="{{ $active === 'pages' ? 'on' : '' }}"><svg class="ic" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>Pages</a>
    <a href="{{ route('buildr.media') }}" class="{{ $active === 'media' ? 'on' : '' }}"><svg class="ic" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>Media</a>
  </nav>
  @if ($updateAvailable)
    <div style="margin:0 12px 10px;padding:9px 11px;border-radius:9px;background:var(--accent-soft);color:var(--accent-soft-ink);font-size:11px;line-height:1.5">
      <b>Buildr update available.</b> Deploy pulls the latest engine automatically.
    </div>
  @endif
  <div class="side-foot" style="display:flex;align-items:center;gap:8px">
    buildr {{ \Composer\InstalledVersions::getPrettyVersion('buildr/buildr') ?? 'dev' }}
    <button data-theme-toggle title="Light / dark mode" style="margin-left:auto;width:26px;height:26px;display:grid;place-items:center;border-radius:7px;color:inherit">
      <svg class="ic" viewBox="0 0 24 24" style="width:14px;height:14px"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    </button>
  </div>
</aside>
