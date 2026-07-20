{{-- $path: wire path to set, $current: current value --}}
<div class="fontpick" wire:ignore x-data="fontPicker(@js($path), @js($current ?? ''))">
  <button type="button" class="in" style="text-align:left;display:flex;justify-content:space-between;align-items:center;gap:8px"
          @click="toggle()" x-init="value && loadPreviewFont(value)">
    <span x-text="value || 'Default'" :style="value ? `font-family:'${value}'` : ''"></span>
    <svg class="ic" viewBox="0 0 24 24" style="width:13px;height:13px;flex:none"><path d="m6 9 6 6 6-6"/></svg>
  </button>
  <div x-show="open" x-cloak @click.outside="open = false" class="fp-pop">
    <input class="in" placeholder="Search fonts…" x-model="q" x-ref="search" @click.stop>
    <div class="fp-list">
      <button type="button" class="fp-item" style="font-style:italic;color:var(--muted)" @click="pick('')">Default</button>
      <template x-for="f in filtered.slice(0, 40)" :key="f">
        <button type="button" class="fp-item" x-init="loadPreviewFont(f)"
                :style="`font-family:'${f}', sans-serif`" x-text="f" @click="pick(f)"></button>
      </template>
      <div class="fld-hint" x-show="filtered.length > 40" style="padding:6px 10px"
           x-text="`${filtered.length - 40} more — keep typing to narrow`"></div>
      <div class="fld-hint" x-show="!filtered.length" style="padding:6px 10px">No matches</div>
    </div>
  </div>
</div>
