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
    </div>
  @endif

  @switch($field['type'])

    @case('text')
      <input class="in" wire:model.blur="{{ $base }}">
      @break

    @case('textarea')
    @case('richtext')
      <textarea class="in" rows="4" wire:model.blur="{{ $base }}"></textarea>
      @break

    @case('code')
      <textarea class="in mono" rows="6" style="font-family:'JetBrains Mono',monospace;font-size:11.5px" wire:model.blur="{{ $base }}"></textarea>
      @break

    @case('number')
      <input class="in" type="number" wire:model.blur="{{ $base }}">
      @break

    @case('select')
      <select class="in" wire:model.change="{{ $base }}">
        <option value="">—</option>
        @foreach ($field['options'] ?? [] as $value => $label)
          <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
      </select>
      @break

    @case('toggle')
      <label class="togglerow" style="cursor:pointer">
        <span>{{ $field['label'] }}</span>
        <input type="checkbox" wire:model.change="{{ $base }}">
      </label>
      @break

    @case('unit')
      <div class="unit-wrap">
        <input class="in" type="number" step="any" wire:model.blur="{{ $base }}.value">
        <select class="unit-sel" wire:model.change="{{ $base }}.unit">
          @foreach ($units as $unit)
            <option value="{{ $unit }}">{{ $unit === '' ? '—' : $unit }}</option>
          @endforeach
        </select>
      </div>
      @break

    @case('color')
      <div class="colorrow">
        <input type="color" class="cp" wire:model.live="{{ $base }}">
        <input class="in hex mono" placeholder="none" wire:model.blur="{{ $base }}">
      </div>
      @break

    @case('sides')
      <div style="display:grid;grid-template-columns:repeat(4,1fr) 58px;gap:6px">
        @foreach (['top' => 'T', 'right' => 'R', 'bottom' => 'B', 'left' => 'L'] as $side => $abbr)
          <div>
            <input class="in" type="number" step="any" title="{{ ucfirst($side) }}"
                   style="padding:9px 6px;text-align:center"
                   wire:model.blur="{{ $base }}.{{ $side }}.value">
            <div style="text-align:center;font-family:'JetBrains Mono',monospace;font-size:8.5px;color:var(--muted-2);margin-top:3px">{{ $abbr }}</div>
          </div>
        @endforeach
        <select class="unit-sel" style="height:37px"
                wire:change="setSidesUnit('{{ $tab }}', '{{ $key }}', $event.target.value{{ $responsive ? ", '{$device}'" : '' }})">
          @foreach ($units as $unit)
            <option value="{{ $unit }}">{{ $unit }}</option>
          @endforeach
        </select>
      </div>
      @break

    @case('link')
      <div class="linkfld">
        <input class="in url mono" placeholder="/page or https://…" wire:model.blur="{{ $base }}.url">
        <label class="togglerow" style="cursor:pointer">
          <span>Open in new tab</span>
          <input type="checkbox" wire:model.change="{{ $base }}.new_tab">
        </label>
      </div>
      @break

    @case('media')
      <input class="in mono" placeholder="https://…/image.jpg" wire:model.blur="{{ $base }}">
      <div class="fld-hint">Paste an image URL — media library lands in a later stage.</div>
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
            <input class="in wnum" type="number" min="5" max="95"
                   wire:model.blur="settings.content.widths.{{ $i }}">
            <span>%</span>
          </div>
        @endforeach
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
