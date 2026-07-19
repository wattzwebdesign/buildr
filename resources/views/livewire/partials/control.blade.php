@php
    /** @var array $field  @var string $tab  @var string $device */
    $key = $field['key'];
    $responsive = $field['responsive'] ?? false;
    $base = "settings.{$tab}.{$key}".($responsive ? ".{$device}" : '');
    $units = $field['units'] ?? ['px'];
@endphp

<div class="fld" wire:key="fld-{{ $selectedId }}-{{ $tab }}-{{ $key }}-{{ $responsive ? $device : 'all' }}">
  @if ($field['type'] !== 'toggle')
    <div class="fld-label">
      {{ $field['label'] }}
      @if ($responsive)
        <span class="ctx-chip" style="margin-left:auto;font-size:8.5px" title="Editing {{ $device }} value">{{ strtoupper(substr($device, 0, 1)) }}</span>
      @endif
      @if ($tab === 'content' && in_array($field['type'], ['text', 'textarea', 'richtext', 'link']) && !empty($dynTags))
        <span x-data="{ o: false }" style="{{ $responsive ? '' : 'margin-left:auto;' }}position:relative">
          <button type="button" title="Insert dynamic tag" @click="o = !o" style="color:var(--accent);display:grid;place-items:center">
            <svg class="ic" viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor;stroke:none"><path d="M13 2 4.5 13.5H11L9.5 22 19 10h-6.5z"/></svg>
          </button>
          <div x-show="o" x-cloak @click.outside="o = false" class="fp-pop" style="left:auto;right:0;width:200px;top:calc(100% + 4px)">
            <div class="fp-list" style="margin-top:0">
              @foreach ($dynTags as $label => $tag)
                <button type="button" class="fp-item" style="font-size:12px" title="{{ $tag }}"
                        @click="insertTag($el, @js($tag)); o = false">{{ $label }}</button>
              @endforeach
            </div>
          </div>
        </span>
      @endif
    </div>
  @endif

  @switch($field['type'])

    @case('text')
      <input class="in" wire:model.live.debounce.400ms="{{ $base }}"
             @if ($tab === 'content') data-mirror="{{ $key }}" data-mirror-mode="text" @endif>
      @break

    @case('textarea')
    @case('richtext')
      <textarea class="in" rows="4" wire:model.live.debounce.400ms="{{ $base }}"
                @if ($tab === 'content') data-mirror="{{ $key }}" data-mirror-mode="{{ $field['type'] === 'richtext' ? 'html' : 'text' }}" @endif></textarea>
      @break

    @case('code')
      <textarea class="in mono" rows="6" style="font-family:'JetBrains Mono',monospace;font-size:11.5px" wire:model.blur="{{ $base }}"></textarea>
      @break

    @case('number')
      <input class="in" type="number" wire:model.live.debounce.400ms="{{ $base }}">
      @break

    @case('select')
      @if (!empty($field['buttons']))
        @php
          $segIcon = fn ($n) => match ($n) {
              'h-start' => '<line x1="4" y1="4" x2="4" y2="20"/><rect x="8" y="9" width="10" height="6" rx="1"/>',
              'h-center' => '<line x1="12" y1="4" x2="12" y2="20" stroke-dasharray="2 2"/><rect x="7" y="9" width="10" height="6" rx="1"/>',
              'h-end' => '<line x1="20" y1="4" x2="20" y2="20"/><rect x="6" y="9" width="10" height="6" rx="1"/>',
              'h-stretch' => '<line x1="4" y1="4" x2="4" y2="20"/><line x1="20" y1="4" x2="20" y2="20"/><rect x="8" y="9" width="8" height="6" rx="1"/>',
              'v-top' => '<line x1="4" y1="4" x2="20" y2="4"/><rect x="9" y="8" width="6" height="10" rx="1"/>',
              'v-center' => '<line x1="4" y1="12" x2="20" y2="12" stroke-dasharray="2 2"/><rect x="9" y="7" width="6" height="10" rx="1"/>',
              'v-bottom' => '<line x1="4" y1="20" x2="20" y2="20"/><rect x="9" y="6" width="6" height="10" rx="1"/>',
              'v-stretch' => '<line x1="4" y1="4" x2="20" y2="4"/><line x1="4" y1="20" x2="20" y2="20"/><rect x="9" y="8" width="6" height="8" rx="1"/>',
              'v-between' => '<line x1="4" y1="4" x2="20" y2="4"/><line x1="4" y1="20" x2="20" y2="20"/><rect x="9" y="6" width="6" height="4" rx="1"/><rect x="9" y="14" width="6" height="4" rx="1"/>',
              'text-left' => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="18" y2="18"/>',
              'text-center' => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="5" y1="18" x2="19" y2="18"/>',
              'text-right' => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="10" y1="12" x2="20" y2="12"/><line x1="6" y1="18" x2="20" y2="18"/>',
              'text-justify' => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/>',
              'boxed' => '<line x1="3" y1="4" x2="3" y2="20"/><line x1="21" y1="4" x2="21" y2="20"/><rect x="7" y="7" width="10" height="10" rx="1.5"/>',
              'full' => '<rect x="3" y="7" width="18" height="10" rx="1.5"/>',
              'ban' => '<circle cx="12" cy="12" r="8"/><line x1="6.5" y1="6.5" x2="17.5" y2="17.5"/>',
              'line-solid' => '<line x1="4" y1="12" x2="20" y2="12"/>',
              'line-dashed' => '<line x1="4" y1="12" x2="20" y2="12" stroke-dasharray="4 3"/>',
              'line-dotted' => '<line x1="4" y1="12" x2="20" y2="12" stroke-dasharray="1 3.5" stroke-linecap="round"/>',
              default => '<rect x="5" y="5" width="14" height="14" rx="2"/>',
          };
          $current = data_get($settings, str_replace('settings.', '', $base));
          $current = is_scalar($current) ? (string) $current : '';
        @endphp
        <div class="segi">
          @foreach ($field['options'] ?? [] as $value => $label)
            <button type="button" class="{{ $current === (string) $value ? 'on' : '' }}" title="{{ $label }}"
                    wire:click="$set('{{ $base }}', '{{ $value }}')">
              <svg class="ic" viewBox="0 0 24 24">{!! $segIcon($field['icons'][$value] ?? '') !!}</svg>
            </button>
          @endforeach
        </div>
      @elseif (count($field['options'] ?? []) > 100)
        @php $fpCurrent = data_get($settings, str_replace('settings.', '', $base)); @endphp
        @include('buildr::livewire.partials.fontpicker', ['path' => $base, 'current' => is_scalar($fpCurrent) ? $fpCurrent : ''])
      @else
        <select class="in" wire:model.change="{{ $base }}">
          <option value="">—</option>
          @foreach ($field['options'] ?? [] as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
          @endforeach
        </select>
      @endif
      @break

    @case('toggle')
      <label class="togglerow" style="cursor:pointer">
        <span>{{ $field['label'] }}</span>
        <input type="checkbox" class="tgi" wire:model.change="{{ $base }}">
        <span class="tg"></span>
      </label>
      @break

    @case('unit')
      <div class="unit-wrap">
        <input class="in" type="number" step="any" data-livecss="{{ $key }}" wire:model.live.debounce.400ms="{{ $base }}.value">
        <select class="unit-sel" data-livecss-unit wire:model.change="{{ $base }}.unit">
          @foreach ($units as $unit)
            <option value="{{ $unit }}">{{ $unit === '' ? '—' : $unit }}</option>
          @endforeach
        </select>
      </div>
      @break

    @case('color')
      @php $cval = data_get($settings, str_replace('settings.', '', $base)); @endphp
      @if (!empty($field['states']))
        <div x-data="{ st: 'normal' }">
          <div class="segi" style="margin-bottom:6px">
            <button type="button" :class="st === 'normal' && 'on'" @click="st = 'normal'" style="font-size:10.5px;font-weight:600">Normal</button>
            <button type="button" :class="st === 'hover' && 'on'" @click="st = 'hover'" style="font-size:10.5px;font-weight:600">Hover</button>
          </div>
          <div x-show="st === 'normal'">
            @include('buildr::livewire.partials.colorpicker', ['path' => $base.'.normal', 'current' => $cval['normal'] ?? '', 'ckey' => $key, 'swatches' => $globalSwatches ?? []])
          </div>
          <div x-show="st === 'hover'" x-cloak>
            @include('buildr::livewire.partials.colorpicker', ['path' => $base.'.hover', 'current' => $cval['hover'] ?? '', 'ckey' => null, 'swatches' => $globalSwatches ?? []])
          </div>
        </div>
      @else
        @include('buildr::livewire.partials.colorpicker', ['path' => $base, 'current' => is_scalar($cval) ? $cval : '', 'ckey' => $key, 'swatches' => $globalSwatches ?? []])
      @endif
      @break

    @case('sides')
      <div data-sides-wrap style="display:grid;grid-template-columns:repeat(4,1fr) 58px;gap:6px">
        @foreach (['top' => 'T', 'right' => 'R', 'bottom' => 'B', 'left' => 'L'] as $side => $abbr)
          <div>
            <input class="in" type="number" step="any" title="{{ ucfirst($side) }}"
                   style="padding:9px 6px;text-align:center"
                   data-livecss="{{ $key }}" data-side="{{ $side }}"
                   wire:model.live.debounce.400ms="{{ $base }}.{{ $side }}.value">
            <div style="text-align:center;font-family:'JetBrains Mono',monospace;font-size:8.5px;color:var(--muted-2);margin-top:3px">{{ $abbr }}</div>
          </div>
        @endforeach
        <select class="unit-sel" data-livecss-unit style="height:37px"
                wire:change="setSidesUnit('{{ $tab }}', '{{ $key }}', $event.target.value{{ $responsive ? ", '{$device}'" : '' }})">
          @foreach ($units as $unit)
            <option value="{{ $unit }}">{{ $unit }}</option>
          @endforeach
        </select>
      </div>
      @break

    @case('link')
      <div class="linkfld">
        <input class="in url mono" placeholder="/page or https://…" wire:model.live.debounce.400ms="{{ $base }}.url">
        <label class="togglerow" style="cursor:pointer">
          <span>Open in new tab</span>
          <input type="checkbox" class="tgi" wire:model.change="{{ $base }}.new_tab">
          <span class="tg"></span>
        </label>
      </div>
      @break

    @case('media')
      <input class="in mono" placeholder="https://…/image.jpg" wire:model.live.debounce.400ms="{{ $base }}">
      <label class="pl-edit" style="display:block;text-align:center;cursor:pointer;margin-top:8px">
        <span wire:loading.remove wire:target="upload">Upload image…</span>
        <span wire:loading wire:target="upload">Uploading…</span>
        <input type="file" accept="image/*" style="display:none" wire:model="upload"
               x-on:click="$wire.set('mediaTarget', '{{ $base }}')">
      </label>
      @error('upload')<div class="fld-hint" style="color:var(--danger)">{{ $message }}</div>@enderror
      @if (!empty($recentMedia))
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:8px">
          @foreach ($recentMedia as $m)
            <button type="button" title="{{ $m['name'] }}"
                    style="aspect-ratio:1;border-radius:6px;border:1px solid var(--panel-line);background:url('{{ $m['url'] }}') center/cover;cursor:pointer"
                    wire:click="$set('{{ $base }}', '{{ $m['url'] }}')"></button>
          @endforeach
        </div>
      @endif
      @break

    @case('columns')
      @php $widths = $settings['content']['widths'] ?? [100]; @endphp
      <div class="preset-row" style="margin-bottom:10px">
        @foreach (['100', '50,50', '33,67', '67,33', '33,33,33', '25,50,25', '25,25,25,25'] as $preset)
          @php $pw = explode(',', $preset); @endphp
          <button type="button" class="preset {{ implode(',', $widths) === $preset ? 'on' : '' }}"
                  wire:click="setWidths('{{ $preset }}')" title="{{ implode(' / ', $pw) }}">
            @foreach ($pw as $w)<span style="flex:{{ $w }}"></span>@endforeach
          </button>
        @endforeach
      </div>
      <div class="wgrid">
        @foreach ($widths as $i => $w)
          <div class="wnum-wrap">
            <input class="in wnum" type="number" min="5" max="95" data-livecss="widths"
                   wire:model.live.debounce.400ms="settings.content.widths.{{ $i }}">
            <span>%</span>
          </div>
        @endforeach
      </div>
      @break

    @case('repeater')
      @php $items = data_get($settings, "{$tab}.{$key}", []); @endphp
      <div class="rep">
        @foreach ($items as $i => $item)
          <div class="rep-item open" wire:key="rep-{{ $selectedId }}-{{ $key }}-{{ $i }}">
            <div class="rep-head">
              <span class="rep-title">#{{ $i + 1 }}</span>
              <button type="button" class="act warn" style="margin-left:auto;color:var(--danger);font-size:11px"
                      wire:click="removeRepeaterItem('{{ $tab }}', '{{ $key }}', {{ $i }})">Remove</button>
            </div>
            <div class="rep-body" style="display:block">
              @foreach ($field['fields'] ?? [] as $sub)
                @php $sbase = "settings.{$tab}.{$key}.{$i}.{$sub['key']}"; @endphp
                <div class="fld" style="margin-top:10px;margin-bottom:0">
                  @if ($sub['type'] !== 'toggle')<div class="fld-label">{{ $sub['label'] }}</div>@endif
                  @if ($sub['type'] === 'select')
                    <select class="in" wire:model.change="{{ $sbase }}">
                      <option value="">—</option>
                      @foreach ($sub['options'] ?? [] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                  @elseif (in_array($sub['type'], ['textarea', 'richtext']))
                    <textarea class="in" rows="3" wire:model.live.debounce.400ms="{{ $sbase }}"></textarea>
                  @elseif ($sub['type'] === 'toggle')
                    <label class="togglerow" style="cursor:pointer"><span>{{ $sub['label'] }}</span>
                      <input type="checkbox" class="tgi" wire:model.change="{{ $sbase }}"><span class="tg"></span></label>
                  @elseif ($sub['type'] === 'number')
                    <input class="in" type="number" wire:model.live.debounce.400ms="{{ $sbase }}">
                  @else
                    <input class="in" wire:model.live.debounce.400ms="{{ $sbase }}">
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
        <button type="button" class="rep-add" wire:click="addRepeaterItem('{{ $tab }}', '{{ $key }}')">+ Add item</button>
      </div>
      @break

    @default
      <div class="fld-hint" style="padding:9px 11px;border:1px dashed var(--panel-line);border-radius:8px">
        {{ ucfirst($field['type']) }} control — coming with its element
      </div>

  @endswitch

  @if (!empty($field['help']))
    <div class="fld-hint">{!! $field['help'] !!}</div>
  @endif
</div>
