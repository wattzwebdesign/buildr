{{-- $path: wire path, $current: value --}}
<div class="fontpick" x-data="iconPicker(@js($path), @js($current ?? ''))">
  <button type="button" class="in" style="text-align:left;display:flex;align-items:center;gap:10px" @click="toggle()">
    <span class="ipk-cur" style="display:grid;place-items:center;width:20px;height:20px">{!! \Buildr\Support\Icons::svg($current ?? '', 18) !!}</span>
    <span x-text="label()" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
    <svg class="ic" viewBox="0 0 24 24" style="width:13px;height:13px;flex:none"><path d="m6 9 6 6 6-6"/></svg>
  </button>
  <div x-show="open" x-cloak @click.outside="open = false" class="fp-pop" style="width:262px">
    <template x-if="!pasteMode">
      <div>
        <input class="in" placeholder="Search icons…" x-model="q" x-ref="search" @click.stop @input="limit = 150">
        <div class="ipk-grid" @scroll.passive="more($event)">
          <template x-for="n in filtered.slice(0, limit)" :key="n">
            <button type="button" class="ipk-item" :title="n" @click="pick(n)" x-html="wrap(icons[n])"></button>
          </template>
        </div>
        <div class="fld-hint" style="padding:4px 2px"
             x-text="`${filtered.length} icons`"></div>
        <div style="display:flex;gap:6px;margin-top:8px">
          <button type="button" class="pl-edit" style="flex:1;text-align:center" @click="pasteMode = true">Paste SVG…</button>
          <button type="button" class="pl-edit" style="text-align:center" @click="pick('')">Clear</button>
        </div>
      </div>
    </template>
    <template x-if="pasteMode">
      <div>
        <textarea class="in mono" rows="5" placeholder="<svg …>…</svg>" x-model="pasted"
                  style="font-family:'JetBrains Mono',monospace;font-size:10.5px"></textarea>
        <div style="display:flex;gap:6px;margin-top:8px">
          <button type="button" class="pl-edit" style="flex:1;text-align:center" @click="applyPaste()">Use SVG</button>
          <button type="button" class="pl-edit" style="text-align:center" @click="pasteMode = false">Back</button>
        </div>
      </div>
    </template>
  </div>
</div>
