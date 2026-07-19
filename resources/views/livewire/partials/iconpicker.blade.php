{{-- $path: wire path, $current: value --}}
<div x-data="iconPicker(@js($path), @js($current ?? ''))">
  <button type="button" class="in" style="text-align:left;display:flex;align-items:center;gap:10px" @click="toggle()">
    <span class="ipk-cur" style="display:grid;place-items:center;width:20px;height:20px">{!! \Buildr\Support\Icons::svg($current ?? '', 18) !!}</span>
    <span x-text="label()" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
    <svg class="ic" viewBox="0 0 24 24" style="width:13px;height:13px;flex:none"><path d="m6 9 6 6 6-6"/></svg>
  </button>

  <template x-teleport="body">
    <div x-show="open" x-cloak class="ipk-overlay" @click.self="open = false" @keydown.escape.window="open = false">
      <div class="ipk-modal">
        <div class="ipk-head">
          <input class="in" placeholder="Search {{ '1,997' }} icons…" x-model="q" x-ref="search" @input="limit = 240">
          <button type="button" class="ipk-x" @click="open = false" title="Close">
            <svg class="ic" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div class="ipk-body" x-show="!pasteMode">
          <div class="ipk-cats">
            <button type="button" :class="cat === 'all' && 'on'" @click="cat = 'all'; limit = 240">
              All <span x-text="names.length"></span>
            </button>
            <template x-for="c in catNames" :key="c">
              <button type="button" :class="cat === c && 'on'" @click="cat = c; limit = 240">
                <span x-text="c.replace('-', ' ')"></span> <span x-text="cats[c].length"></span>
              </button>
            </template>
          </div>
          <div class="ipk-gridwrap" @scroll.passive="more($event)">
            <div class="ipk-grid ipk-grid-lg">
              <template x-for="n in filtered.slice(0, limit)" :key="n">
                <button type="button" class="ipk-item" :title="n" @click="pick(n)" x-html="wrap(icons[n])"></button>
              </template>
            </div>
            <div class="fld-hint" style="padding:8px 4px" x-text="`${filtered.length} icons`"></div>
          </div>
        </div>

        <div class="ipk-body" x-show="pasteMode" x-cloak style="display:block;padding:14px">
          <textarea class="in mono" rows="7" placeholder="<svg …>…</svg>" x-model="pasted"
                    style="font-family:'JetBrains Mono',monospace;font-size:11px"></textarea>
        </div>

        <div class="ipk-foot">
          <button type="button" class="pl-edit" x-show="!pasteMode" @click="pasteMode = true">Paste SVG…</button>
          <button type="button" class="pl-edit" x-show="pasteMode" x-cloak @click="applyPaste()">Use SVG</button>
          <button type="button" class="pl-edit" x-show="pasteMode" x-cloak @click="pasteMode = false">Back</button>
          <span style="flex:1"></span>
          <button type="button" class="pl-edit" @click="pick('')">Clear icon</button>
        </div>
      </div>
    </div>
  </template>
</div>
