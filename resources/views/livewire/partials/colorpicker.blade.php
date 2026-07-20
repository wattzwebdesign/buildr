{{-- $path: wire path, $current: value, $ckey: livecss key|null, $swatches: global swatches --}}
<div class="cpk" wire:ignore x-data="colorPicker(@js($path), @js($current ?? ''), @js($ckey ?? null), @js($swatches ?? []))">
  <button type="button" class="cpk-swatch" @click="toggle()" :style="`background:${display() || 'transparent'}`"
          :class="!display() && 'empty'" title="Pick color"></button>
  <div x-show="open" x-cloak @click.outside="open = false" class="cpk-pop">
    <div class="cpk-sv" :style="`background:linear-gradient(to top,#000,transparent),linear-gradient(to right,#fff,hsl(${h},100%,50%))`"
         @pointerdown="drag = 'sv'; svMove($event); $event.target.setPointerCapture($event.pointerId)"
         @pointermove="drag === 'sv' && svMove($event)"
         @pointerup="drag = null; push()">
      <span class="cpk-dot" :style="`left:${s * 100}%;top:${(1 - v) * 100}%`"></span>
    </div>
    <div class="cpk-hue"
         @pointerdown="drag = 'hue'; hueMove($event); $event.target.setPointerCapture($event.pointerId)"
         @pointermove="drag === 'hue' && hueMove($event)"
         @pointerup="drag = null; push()">
      <span class="cpk-knob" :style="`left:${h / 360 * 100}%`"></span>
    </div>
    <div style="display:flex;gap:6px;align-items:center;margin-top:8px">
      <input class="in mono" style="flex:1;font-size:11.5px" placeholder="#000000"
             :value="hex" @change="setHex($event.target.value)" @keydown.enter.prevent="setHex($event.target.value)">
      <button type="button" class="cpk-clear" title="Clear color" @click="clear()">
        <svg class="ic" viewBox="0 0 24 24" style="width:13px;height:13px"><circle cx="12" cy="12" r="8"/><line x1="6.5" y1="6.5" x2="17.5" y2="17.5"/></svg>
      </button>
    </div>
    <template x-if="swatches.length">
      <div class="swatches mini" style="margin-top:8px">
        <template x-for="sw in swatches" :key="sw.var">
          <button type="button" class="sw" :style="`background:${sw.value}`" :title="sw.name + ' — global'"
                  @click="pickGlobal(sw)"></button>
        </template>
      </div>
    </template>
  </div>
</div>
