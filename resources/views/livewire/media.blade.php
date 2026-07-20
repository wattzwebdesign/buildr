<div class="dash">
  @include('buildr::livewire.partials.sidenav', ['active' => 'media', 'updateAvailable' => $updateAvailable])

  <main class="main">
    <div class="main-head">
      <h1>Media</h1>
      <span class="count">{{ $total }} {{ Str::plural('FILE', $total) }}</span>
      <label class="btn-primary" style="margin-left:auto;cursor:pointer">
        <span wire:loading.remove wire:target="uploads" style="display:flex;align-items:center;gap:7px">
          <svg class="ic" viewBox="0 0 24 24" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>Upload
        </span>
        <span wire:loading wire:target="uploads">Uploading…</span>
        <input type="file" accept="image/*" multiple style="display:none" wire:model="uploads">
      </label>
    </div>
    @error('uploads.*')<div style="font-size:11px;color:var(--danger);margin-bottom:10px">{{ $message }}</div>@enderror

    <div class="searchbar">
      <svg class="ic" viewBox="0 0 24 24" style="width:14px;height:14px"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
      <input placeholder="Search media…" wire:model.live.debounce.300ms="search">
    </div>

    <div class="media-grid">
      @forelse ($items as $m)
        <div class="mcard" wire:key="media-{{ $m['id'] }}" x-data="{ copied: false }">
          <a class="mcard-thumb" href="{{ $m['url'] }}" target="_blank" title="Open full size"
             style="background-image:url('{{ $m['url'] }}')"></a>
          <div class="mcard-body">
            <span class="mcard-name" title="{{ $m['name'] }}">{{ $m['name'] }}</span>
            <span class="mcard-meta">{{ $m['size'] }} · {{ $m['date'] }}</span>
            <span class="mcard-meta" style="{{ $m['used'] ? 'color:var(--accent)' : '' }}">
              {{ $m['used'] ? 'Used on '.$m['used'].' '.Str::plural('page', $m['used']) : 'Not in use' }}
            </span>
          </div>
          <div class="mcard-actions">
            <button type="button" class="pl-edit" style="flex:1"
                    @click="navigator.clipboard.writeText('{{ $m['url'] }}'); copied = true; setTimeout(() => copied = false, 1200)">
              <span x-show="!copied">Copy URL</span><span x-show="copied" style="color:var(--accent)">Copied ✓</span>
            </button>
            <button type="button" class="pl-edit" style="color:var(--danger)" title="Delete"
                    @click="bConfirm(@js($m['used'] ? 'Delete “'.$m['name'].'”? It is used on '.$m['used'].' '.Str::plural('page', $m['used']).' — those images will stop loading.' : 'Delete “'.$m['name'].'”?'), { danger: true }).then(ok => ok && $wire.deleteMedia({{ $m['id'] }}))">✕</button>
          </div>
        </div>
      @empty
        <div style="grid-column:1/-1;color:var(--muted);font-size:13px;padding:30px 0;text-align:center">
          {{ $total ? 'No files match your search.' : 'No uploads yet — images you upload in the editor land here, or upload directly above.' }}
        </div>
      @endforelse
    </div>
  </main>
</div>
