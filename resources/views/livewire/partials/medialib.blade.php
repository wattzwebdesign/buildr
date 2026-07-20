{{-- Editor "choose from library" modal. State lives in an Alpine store so
     Livewire morphs never reset it; the grid itself is server-rendered so
     fresh uploads appear without a reload. Open via $store.mlib.show(path). --}}
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.store('mlib', {
      open: false,
      target: '',
      q: '',
      show(target) { this.target = target; this.q = ''; this.open = true; },
    });
  });
</script>

<div x-show="$store.mlib.open" style="display:none" class="ipk-overlay"
     @click.self="$store.mlib.open = false" @keydown.escape.window="$store.mlib.open = false">
  <div class="ipk-modal" style="max-width:760px">
    <div class="ipk-head">
      <input class="in" placeholder="Search {{ count($allMedia) }} {{ Str::plural('file', count($allMedia)) }}…" x-model="$store.mlib.q">
      <button type="button" class="ipk-x" @click="$store.mlib.open = false" title="Close">
        <svg class="ic" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="mlb-grid">
      @forelse ($allMedia as $m)
        <button type="button" class="mlb-item" wire:key="mlb-{{ $m['id'] }}" title="{{ $m['name'] }}"
                data-name="{{ strtolower($m['name']) }}" data-url="{{ $m['url'] }}"
                x-show="!$store.mlib.q || $el.dataset.name.includes($store.mlib.q.toLowerCase())"
                @click="$wire.set($store.mlib.target, $el.dataset.url); $store.mlib.open = false">
          <span class="mlb-thumb" style="background-image:url('{{ $m['url'] }}')"></span>
          <span class="mlb-name">{{ $m['name'] }}</span>
          <span class="mlb-meta">{{ $m['size'] }} · {{ $m['date'] }}</span>
        </button>
      @empty
        <div style="grid-column:1/-1;color:var(--muted);font-size:12.5px;padding:26px 0;text-align:center">
          No uploads yet — use an Upload button to add your first image.
        </div>
      @endforelse
    </div>
  </div>
</div>
