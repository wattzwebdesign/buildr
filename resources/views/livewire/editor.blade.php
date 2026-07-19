@php
    $icon = fn (string $name) => match ($name) {
        'layout' => '<rect x="3" y="5" width="18" height="14" rx="2"/><line x1="12" y1="5" x2="12" y2="19"/>',
        'heading' => '<path d="M6 4v16M18 4v16M6 12h12"/>',
        'text' => '<path d="M4 7V5h16v2M12 5v14M9 19h6"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-4.5-4.5L7 21"/>',
        'button' => '<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M8 12h8"/>',
        'divider' => '<line x1="3" y1="12" x2="21" y2="12" stroke-dasharray="3 3"/>',
        'spacer' => '<path d="M12 5v14M8 8l4-3 4 3M8 16l4 3 4-3"/>',
        'video' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="m10 9 5 3-5 3z"/>',
        'map' => '<path d="M12 21s-7-5.3-7-11a7 7 0 0 1 14 0c0 5.7-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'code' => '<path d="m8 8-4 4 4 4M16 8l4 4-4 4"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'icon-box' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="12" cy="9" r="2.5"/><path d="M8 16h8"/>',
        'list' => '<line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="5" cy="6" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="5" cy="18" r="1"/>',
        'share' => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.3 10.8 7.4-3.6M8.3 13.2l7.4 3.6"/>',
        'accordion' => '<rect x="3" y="4" width="18" height="5" rx="1.5"/><rect x="3" y="12" width="18" height="8" rx="1.5"/><path d="m10 15.5 2 2 2-2"/>',
        'tabs' => '<path d="M3 9h18v11H3z"/><path d="M3 9V5h6l2 4"/>',
        'gallery' => '<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/>',
        'form' => '<rect x="3" y="4" width="18" height="6" rx="1.5"/><rect x="3" y="14" width="18" height="6" rx="1.5"/><path d="M6 7h6"/>',
        default => '<rect x="4" y="4" width="16" height="16" rx="2"/>',
    };
@endphp
<div class="app">
<style>
body{overflow:hidden}
[data-bnode]:not(section){cursor:pointer}
.page-frame [data-bnode]:hover{outline:1px dashed rgba(255,178,0,.65);outline-offset:2px}
[data-bhidden]{opacity:.35}
.page-frame .bcol{display:flex;flex-direction:column;gap:12px;min-width:0}
.bcol-ph{
  border:1.5px dashed rgba(150,150,160,.5);border-radius:8px;min-height:110px;
  display:grid;place-items:center;font-family:Archivo,sans-serif;font-size:12px;
  color:#8b8f98;cursor:pointer;transition:.15s;
}
.bcol-ph:not(.mini){flex:1;align-self:stretch}
.bcol-ph.mini{min-height:34px;opacity:0;font-size:15px}
.bcol:hover .bcol-ph.mini{opacity:.65}
.bcol-ph:hover,.bcol-ph.drop-hot{border-color:var(--accent);color:var(--accent);background:rgba(255,178,0,.06);opacity:1 !important}
[data-bcontainer].drop-hot,.bcol.drop-hot{outline:2px dashed var(--accent);outline-offset:-2px}
.page-frame [data-bnode].drop-before{box-shadow:0 -3px 0 0 var(--accent) !important}
.page-frame [data-bnode].drop-after{box-shadow:0 3px 0 0 var(--accent) !important}
.page-frame [data-bnode][draggable]{cursor:grab}
.addgap.drop-hot{height:36px !important;background:rgba(255,178,0,.12);outline:2px dashed var(--accent);outline-offset:-2px;border-radius:6px}
.addgap.drop-hot button{opacity:1;transform:translateX(-50%) scale(1)}
#el-tools{
  position:fixed;z-index:80;display:none;align-items:center;
  background:var(--accent);color:var(--accent-ink);border-radius:6px;overflow:hidden;
  box-shadow:0 4px 14px rgba(0,0,0,.3);
}
#el-tools.show{display:flex}
#el-tools button{width:26px;height:24px;display:grid;place-items:center;transition:.12s}
#el-tools button:hover{background:rgba(0,0,0,.14)}
#el-tools svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
#elt-drag{cursor:grab}
.page-frame.child-hover .sec-tools{display:none}
/* every container AND column shows faint dashed bounds in the editor, Elementor-style */
.page-frame [data-bcontainer],.page-frame .bcol{outline:1px dashed rgba(150,153,163,.45);outline-offset:-2px}
.page-frame [data-bcontainer]:hover{outline-color:rgba(255,178,0,.6)}
#el-tools #elt-type{width:26px;height:24px;display:grid;place-items:center;background:rgba(0,0,0,.14);cursor:pointer}
#el-tools #elt-type svg{display:none}
#el-tools.is-container #elt-type svg[data-t="container"]{display:block}
#el-tools:not(.is-container) #elt-type svg[data-t="element"]{display:block}
@if ($selectedId && $isChild)
.page-frame [data-bnode="{{ $selectedId }}"]{outline:2px solid var(--accent) !important;outline-offset:2px}
@endif
</style>

  <!-- ============ LEFT PANEL ============ -->
  <aside class="panel">
    <div class="panel-head">
      <a class="ph-btn" href="{{ route('buildr.pages') }}" title="Back to pages" style="display:grid;place-items:center">
        <svg class="ic" viewBox="0 0 24 24"><path d="m12 19-7-7 7-7M5 12h14"/></svg>
      </a>
      <div class="ph-title">
        <span class="bolt"><svg viewBox="0 0 24 24"><path d="M13 2 4.5 13.5H11L9.5 22 19 10h-6.5z"/></svg></span>
        Buildr
      </div>
      <button class="ph-btn {{ $view === 'library' ? 'lib-on' : '' }}" title="Add section / element"
              wire:click="{{ $view === 'library' ? 'closeLibrary' : 'openLibrary' }}" style="display:grid;place-items:center">
        <svg class="ic" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      </button>
      <button class="ph-btn" data-theme-toggle title="Light / dark mode" style="display:grid;place-items:center">
        <svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>
    </div>

    @if ($view === 'library')
      <!-- ============ SIDEBAR LIBRARY ============ -->
      <div class="controls" x-data="{ q: '' }">
        <div class="lib-search">
          <svg viewBox="0 0 24 24" class="ic"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
          <input placeholder="Search elements…" x-model="q">
        </div>

        <div class="ctl-group"><span>Layout</span></div>
        <div class="lib-cards">
          @foreach ([1 => 'Container', 2 => '2 Columns', 3 => '3 Columns', 4 => '4 Columns'] as $cols => $label)
            <button class="el-card" draggable="true" data-etype="container" data-cols="{{ $cols }}"
                    x-show="!q || '{{ strtolower($label) }}'.includes(q.toLowerCase())"
                    wire:click="addContainer({{ $cols }})">
              <svg class="ic" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/>@for ($i = 1; $i < $cols; $i++)<line x1="{{ 3 + 18 * $i / $cols }}" y1="5" x2="{{ 3 + 18 * $i / $cols }}" y2="19"/>@endfor</svg>
              <span>{{ $label }}</span>
            </button>
          @endforeach
        </div>

        <div class="ctl-group"><span>Elements</span></div>
        <div class="lib-cards">
          @foreach ($library->get('elements', []) as $item)
            <button class="el-card" draggable="true" data-etype="{{ $item['key'] }}"
                    x-show="!q || '{{ strtolower($item['label']) }}'.includes(q.toLowerCase())"
                    wire:click="addElement('{{ $item['key'] }}')">
              <svg class="ic" viewBox="0 0 24 24">{!! $icon($item['icon']) !!}</svg>
              <span>{{ $item['label'] }}</span>
            </button>
          @endforeach
        </div>

        <div class="ctl-group"><span>Pre-built Sections</span></div>
        @if ($library->get('sections', collect())->isEmpty())
          <div class="fld-hint">This site has no coded section blocks yet — scaffold one with <span class="mono">php artisan buildr:make-block</span>.</div>
        @else
          <div class="lib-cards">
            @foreach ($library->get('sections') as $item)
              <button class="el-card" wire:click="addElement('{{ $item['key'] }}')">
                <svg class="ic" viewBox="0 0 24 24"><path d="m12 2 9 5-9 5-9-5 9-5z"/><path d="m3 12 9 5 9-5"/></svg>
                <span>{{ $item['label'] }}</span>
              </button>
            @endforeach
          </div>
        @endif

        <div class="fld-hint" style="margin-top:14px">
          Containers insert {{ $insertAfter !== null ? 'at the spot you picked' : 'at the end of the page' }}.
          Elements: click to add to the selected container, or <b>drag onto any column</b> in the canvas.
        </div>
      </div>
    @elseif ($view === 'site')
      <!-- ============ SITE SETTINGS ============ -->
      <div class="controls">
        <button class="lib-back" wire:click="closeLibrary">
          <svg class="ic" viewBox="0 0 24 24"><path d="m12 19-7-7 7-7M5 12h14"/></svg> Back to editing
        </button>

        <div class="ctl-group"><span>Site</span></div>
        <div class="fld"><div class="fld-label">Site name</div><input class="in" wire:model.blur="site.name"></div>
        <div class="fld"><div class="fld-label">Phone</div><input class="in" wire:model.blur="site.phone">
          <div class="fld-hint">Available anywhere as <span class="mono">@{{site.phone}}</span></div></div>
        <div class="fld"><div class="fld-label">Email</div><input class="in" wire:model.blur="site.email"></div>
        <div class="fld"><div class="fld-label">Address</div><input class="in" wire:model.blur="site.address"></div>

        <div class="ctl-group"><span>Global Colors</span></div>
        @foreach ($site['colors'] ?? [] as $i => $color)
          <div class="fld" wire:key="gcolor-{{ $i }}" style="display:flex;gap:8px;align-items:center">
            @include('buildr::livewire.partials.colorpicker', ['path' => "site.colors.{$i}.value", 'current' => $color['value'] ?? '', 'ckey' => null, 'swatches' => []])
            <input class="in" style="flex:1" wire:model.blur="site.colors.{{ $i }}.name">
            <button type="button" style="color:var(--danger);font-size:14px" wire:click="removeGlobalColor({{ $i }})">✕</button>
          </div>
        @endforeach
        <button type="button" class="rep-add" wire:click="addGlobalColor">+ Add global color</button>
        <div class="fld-hint" style="margin-top:8px">Globals become CSS variables — every color picker shows them as quick swatches. Change one here, it updates site-wide.</div>

        <div class="ctl-group"><span>Global Fonts</span></div>
        <div class="fld"><div class="fld-label">Heading font</div>
          <div class="unit-wrap"><div style="flex:1;min-width:0">@include('buildr::livewire.partials.fontpicker', ['path' => 'site.font_heading', 'current' => $site['font_heading'] ?? ''])</div>
          <select class="unit-sel" style="width:70px" wire:model.change="site.font_heading_weight">
            <option value="">wt</option>@foreach ([300,400,500,600,700,800] as $w)<option>{{ $w }}</option>@endforeach
          </select></div></div>
        <div class="fld"><div class="fld-label">Body font</div>
          <div class="unit-wrap"><div style="flex:1;min-width:0">@include('buildr::livewire.partials.fontpicker', ['path' => 'site.font_body', 'current' => $site['font_body'] ?? ''])</div>
          <select class="unit-sel" style="width:70px" wire:model.change="site.font_body_weight">
            <option value="">wt</option>@foreach ([300,400,500,600] as $w)<option>{{ $w }}</option>@endforeach
          </select></div></div>
        <div class="fld"><div class="fld-label">Base font size (px)</div><input class="in" type="number" wire:model.blur="site.base_size" placeholder="16"></div>
      </div>
    @else
      <!-- ============ EDIT PANEL ============ -->
      <div class="panel-context">
        <div>
          <div class="ctx-kicker">Editing · {{ $page->title }}</div>
          <div class="ctx-name">{{ $schema['label'] ?? 'Nothing selected' }}</div>
        </div>
        <span class="ctx-chip">{{ strtoupper($schema['group'] ?? '—') }}</span>
      </div>

      <div class="tabs">
        @foreach (['content' => 'Content', 'style' => 'Style', 'advanced' => 'Advanced'] as $key => $label)
          <button class="tab {{ $tab === $key ? 'on' : '' }}" wire:click="setTab('{{ $key }}')">{{ $label }}</button>
        @endforeach
      </div>

      <div class="controls">
        @if (! $schema)
          <div class="fld-hint">Click a section or element in the canvas, or add one from the library (grid icon above).</div>
        @endif

        @php
          $grouped = [];
          foreach ($fields as $fieldDef) {
              $sec = $fieldDef['section'] ?? null;
              if (! $grouped || end($grouped)['name'] !== $sec) {
                  $grouped[] = ['name' => $sec, 'fields' => []];
              }
              $grouped[array_key_last($grouped)]['fields'][] = $fieldDef;
          }
        @endphp
        @foreach ($grouped as $chunk)
          @if ($chunk['name'])
            <div class="fsec" x-data="{ open: false }" wire:key="fsec-{{ $selectedId }}-{{ $tab }}-{{ $chunk['name'] }}">
              <button type="button" class="fsec-h" @click="open = !open">
                {{ $chunk['name'] }}
                <svg class="ic" viewBox="0 0 24 24" :style="open && 'transform:rotate(180deg)'"><path d="m6 9 6 6 6-6"/></svg>
              </button>
              <div x-show="open" class="fsec-b">
                @foreach ($chunk['fields'] as $field)
                  @include('buildr::livewire.partials.control', ['field' => $field, 'tab' => $tab, 'device' => $device])
                @endforeach
              </div>
            </div>
          @else
            @foreach ($chunk['fields'] as $field)
              @include('buildr::livewire.partials.control', ['field' => $field, 'tab' => $tab, 'device' => $device])
            @endforeach
          @endif
        @endforeach

        @if ($schema && $isChild)
          <div class="ctl-group"><span>Element</span></div>
          <div style="display:flex;gap:8px">
            <button class="pl-edit" style="flex:1;justify-content:center;display:flex" wire:click="duplicateNode({{ $selectedId }})">Duplicate</button>
            <button class="pl-edit" style="flex:1;justify-content:center;display:flex;color:var(--danger)"
                    wire:click="deleteNode({{ $selectedId }})" wire:confirm="Delete this element?">Delete</button>
          </div>
        @endif
      </div>
    @endif

    @php
        $isDirty = ! $page->published_at || $page->updated_at->gt($page->published_at);
    @endphp
    <div class="panel-foot">
      <button class="pf-btn {{ $view === 'site' ? 'on' : '' }}" wire:click="openSite" title="Site Settings" style="display:grid;place-items:center">
        <svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.09a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34h.09a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87v.09a1.7 1.7 0 0 0 1.55 1H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.55 1z"/></svg>
      </button>
      <button class="pf-btn {{ $showNav ? 'on' : '' }}" wire:click="toggleNav" title="Navigator" style="display:grid;place-items:center">
        <svg class="ic" viewBox="0 0 24 24"><path d="m12 2 9 5-9 5-9-5 9-5z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></svg>
      </button>
      <span style="font-family:'JetBrains Mono',monospace;font-size:9.5px;color:var(--chrome-muted);padding:0 8px">
        <span wire:loading.remove>
          @if (! $page->isPublished()) DRAFT
          @elseif ($isDirty) UNPUBLISHED CHANGES
          @else PUBLISHED
          @endif
        </span>
        <span wire:loading style="color:var(--accent)">SAVING…</span>
      </span>
      <button class="publish {{ $isDirty ? 'dirty' : '' }}" wire:click="publish" style="margin-left:auto"
              @if (! $isDirty && $page->isPublished()) disabled @endif>
        <span wire:loading.remove wire:target="publish">{{ $page->isPublished() ? 'Update' : 'Publish' }}</span>
        <span wire:loading wire:target="publish">Saving…</span>
        <span class="dot"></span>
      </button>
    </div>
  </aside>

  <!-- ============ CANVAS ============ -->
  <main class="canvas {{ $device === 'tablet' ? 'dev-tablet' : ($device === 'mobile' ? 'dev-mobile' : '') }}">
    <div class="canvas-bar">
      <div class="crumb">
        <span class="site">{{ \Buildr\Models\SiteSetting::get('name', config('app.name')) }}</span>
        <span class="sep">/</span>
        <span>{{ $page->title }}</span>
        <span class="status mono">{{ strtoupper($page->updated_at->diffForHumans(short: true)) }}</span>
      </div>
      <div class="devices" title="Preview + edit responsive values for this device">
        <button class="{{ $device === 'desktop' ? 'on' : '' }}" wire:click="setDevice('desktop')" title="Desktop">
          <svg class="ic" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        </button>
        <button class="{{ $device === 'tablet' ? 'on' : '' }}" wire:click="setDevice('tablet')" title="Tablet">
          <svg class="ic" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18.01"/></svg>
        </button>
        <button class="{{ $device === 'mobile' ? 'on' : '' }}" wire:click="setDevice('mobile')" title="Mobile">
          <svg class="ic" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18.01"/></svg>
        </button>
      </div>
      @if ($page->isPublished())
        <a href="/{{ $page->slug }}" target="_blank" style="color:var(--accent);font-size:12px;font-weight:600;text-decoration:none">View live ↗</a>
      @endif
    </div>

    <div class="canvas-scroll">
      <div class="page-frame buildr-page"
           x-data
           @click.prevent="
             const btn = $event.target.closest('[data-tree]'); if (btn) return;
             const ph = $event.target.closest('[data-bcolph]');
             if (ph) { $wire.openLibraryFor(parseInt(ph.dataset.bcolph.split(':')[0])); return; }
             const n = $event.target.closest('[data-bnode]');
             if (n && !n.closest('.sec-tools')) { $wire.selectNode(parseInt(n.dataset.bnode)); return; }
             const s = $event.target.closest('.pv-sec');
             if (s) $wire.selectNode(parseInt(s.dataset.root));
           ">
        {!! \Buildr\Render\GlobalCss::fontLink() !!}
        {!! '<style>'.\Buildr\Render\BaseCss::css().\Buildr\Render\GlobalCss::css().$rendered['css'].'</style>' !!}

        @forelse ($rendered['roots'] as $i => $root)
          <div class="addgap" data-gap-after="{{ $i === 0 ? 0 : $rendered['roots'][$i - 1]['id'] }}">
            <button data-tree title="Add section here"
                    wire:click="openLibrary({{ $i === 0 ? 0 : $rendered['roots'][$i - 1]['id'] }})">
              <svg class="ic" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
          </div>
          <section class="pv-sec {{ $selectedId === $root['id'] ? 'sel' : '' }}"
                   wire:key="sec-{{ $root['id'] }}" data-root="{{ $root['id'] }}">
            <div class="sec-tools">
              <span class="lbl">{{ $root['label'] }}</span>
              <button data-tree wire:click="moveNode({{ $root['id'] }}, 'up')" title="Move up"><svg class="ic" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg></button>
              <button data-tree wire:click="moveNode({{ $root['id'] }}, 'down')" title="Move down"><svg class="ic" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></button>
              <button data-tree wire:click="duplicateNode({{ $root['id'] }})" title="Duplicate"><svg class="ic" viewBox="0 0 24 24"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg></button>
              <button data-tree wire:click="deleteNode({{ $root['id'] }})" wire:confirm="Delete this section and everything in it?" title="Delete"><svg class="ic" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M6 6l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14"/></svg></button>
            </div>
            {!! $root['html'] !!}
          </section>
        @empty
          <div class="addgap" data-gap-after="0" style="height:auto;padding:80px 40px;text-align:center;color:var(--muted);font-size:14px">
            Empty page — add your first section from the library, or drag anything here.
            <div style="margin-top:14px"><button data-tree class="btn-primary" style="margin:0 auto" wire:click="openLibrary">Open library</button></div>
          </div>
        @endforelse

        @if (count($rendered['roots']))
          <div class="addgap" data-gap-after="{{ end($rendered['roots'])['id'] }}" style="height:22px">
            <button data-tree title="Add section at end" wire:click="openLibrary({{ end($rendered['roots'])['id'] }})">
              <svg class="ic" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
          </div>
        @endif
      </div>
    </div>

    <script data-navigate-once>
      window.__buildrFonts = @js(\Buildr\Support\Fonts::picker());
      window.__loadedFonts = window.__loadedFonts || new Set();
      window.loadPreviewFont = (f) => {
        if (!f || window.__loadedFonts.has(f)) return;
        window.__loadedFonts.add(f);
        const l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(f)
               + '&text=' + encodeURIComponent(f + 'Default') + '&display=swap';
        document.head.appendChild(l);
      };
      window.insertTag = (el, token) => {
        const fld = el.closest('.fld');
        const inp = fld?.querySelector('input.in, textarea.in');
        if (!inp) return;
        const s = inp.selectionStart ?? inp.value.length, e = inp.selectionEnd ?? s;
        inp.value = inp.value.slice(0, s) + token + inp.value.slice(e);
        inp.dispatchEvent(new Event('input', { bubbles: true }));
      };
      window.colorPicker = (path, initial, ckey, swatches) => ({
        open: false, drag: null, h: 0, s: 1, v: 1, hex: initial || '', swatches: swatches || [],
        display() {
          if (!this.hex) return '';
          if (this.hex.startsWith('var(')) {
            const m = this.swatches.find(sw => sw.var === this.hex);
            return m ? m.value : '';
          }
          return this.hex;
        },
        toggle() { this.open = !this.open; if (this.open) this.parse(this.display() || '#ff5533'); },
        parse(hex) {
          const m = /^#?([0-9a-f]{6})$/i.exec(hex.trim()); if (!m) return;
          const n = parseInt(m[1], 16), r = (n >> 16) / 255, g = ((n >> 8) & 255) / 255, b = (n & 255) / 255;
          const mx = Math.max(r, g, b), mn = Math.min(r, g, b), d = mx - mn;
          this.v = mx; this.s = mx ? d / mx : 0;
          this.h = d === 0 ? 0 : 60 * (mx === r ? ((g - b) / d + 6) % 6 : mx === g ? (b - r) / d + 2 : (r - g) / d + 4);
        },
        toHex() {
          const f = n => { const k = (n + this.h / 60) % 6, c = this.v - this.v * this.s * Math.max(0, Math.min(k, 4 - k, 1));
            return Math.round(c * 255).toString(16).padStart(2, '0'); };
          return '#' + f(5) + f(3) + f(1);
        },
        svMove(e) { const r = e.currentTarget.getBoundingClientRect();
          this.s = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width));
          this.v = 1 - Math.min(1, Math.max(0, (e.clientY - r.top) / r.height));
          this.hex = this.toHex(); this.live(); },
        hueMove(e) { const r = e.currentTarget.getBoundingClientRect();
          this.h = Math.min(359.9, Math.max(0, (e.clientX - r.left) / r.width * 360));
          this.hex = this.toHex(); this.live(); },
        setHex(val) { const m = /^#?([0-9a-f]{6})$/i.exec(val.trim());
          if (!m) return; this.hex = '#' + m[1].toLowerCase(); this.parse(this.hex); this.live(); this.push(); },
        live() { if (ckey) window.__liveColor?.(ckey, this.hex); },
        push() { window.Livewire.all()[0]?.$wire.set(path, this.hex); },
        pickGlobal(sw) { this.hex = sw.var; if (ckey) window.__liveColor?.(ckey, sw.value);
          window.Livewire.all()[0]?.$wire.set(path, sw.var); this.open = false; },
        clear() { this.hex = ''; if (ckey) window.__liveColor?.(ckey, '');
          window.Livewire.all()[0]?.$wire.set(path, ''); this.open = false; },
      });
      window.fontPicker = (path, initial) => ({
        open: false, q: '', value: initial,
        get filtered() {
          const q = this.q.toLowerCase();
          return q ? window.__buildrFonts.filter(f => f.toLowerCase().includes(q)) : window.__buildrFonts;
        },
        toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.$refs.search?.focus()); },
        pick(f) {
          this.value = f; this.open = false; this.q = '';
          window.Livewire.all()[0]?.$wire.set(path, f);
          if (f) window.loadPreviewFont(f);
        },
      });
      (() => {
        if (window.__buildrDnd) return;
        window.__buildrDnd = true;

        let drag = null;      // {kind:'new',type,cols} | {kind:'move',id}
        let hotEl = null;     // column/container highlight
        let lineEl = null;    // element with before/after indicator

        const clear = () => {
          hotEl?.classList.remove('drop-hot'); hotEl = null;
          lineEl?.classList.remove('drop-before', 'drop-after'); lineEl = null;
        };
        const wire = () => window.Livewire.all()[0]?.$wire;
        const colTarget = e => e.target.closest('[data-bcolph], [data-bcol], [data-bcontainer]');
        const colInfo = t => {
          const raw = t.dataset.bcolph || t.dataset.bcol || (t.dataset.bcontainer + ':0');
          const [id, col] = raw.split(':');
          return { id: parseInt(id), col: parseInt(col || '0') };
        };

        document.addEventListener('dragstart', e => {
          const card = e.target.closest('.el-card[data-etype]');
          if (card) {
            drag = { kind: 'new', type: card.dataset.etype, cols: parseInt(card.dataset.cols || '1') };
            e.dataTransfer.setData('text/plain', 'buildr');
            return;
          }
          if (e.target.closest('#elt-drag')) {
            if (toolsFor) {
              drag = { kind: 'move', id: toolsFor };
              e.dataTransfer.setData('text/plain', 'buildr');
            }
            return;
          }
          const el = e.target.closest('.page-frame [data-bnode][draggable]');
          if (el) {
            drag = { kind: 'move', id: parseInt(el.dataset.bnode) };
            e.dataTransfer.setData('text/plain', 'buildr');
            e.stopPropagation();
          }
        });

        document.addEventListener('dragover', e => {
          if (!drag) return;

          // moving: hovering a NON-container element shows a before/after
          // line; containers mean "drop INTO me" and fall through below
          if (drag.kind === 'move') {
            const el = e.target.closest('.page-frame [data-bnode][draggable]');
            if (el && !el.dataset.bcontainer && parseInt(el.dataset.bnode) !== drag.id) {
              e.preventDefault();
              const rect = el.getBoundingClientRect();
              const before = e.clientY < rect.top + rect.height / 2;
              if (lineEl !== el) { clear(); lineEl = el; }
              el.classList.toggle('drop-before', before);
              el.classList.toggle('drop-after', !before);
              return;
            }
          }

          // page-level "+" gaps accept new containers/elements
          const gap = e.target.closest('.addgap[data-gap-after]');
          if (gap && drag.kind === 'new') {
            e.preventDefault();
            if (hotEl !== gap) { clear(); hotEl = gap; gap.classList.add('drop-hot'); }
            return;
          }

          const target = colTarget(e);
          if (!target) { clear(); return; }
          e.preventDefault();
          if (hotEl !== target) { clear(); hotEl = target; target.classList.add('drop-hot'); }
        });

        document.addEventListener('drop', e => {
          if (!drag) return;
          const w = wire();
          const finish = () => { clear(); drag = null; };

          if (drag.kind === 'move' && lineEl) {
            e.preventDefault();
            const pos = lineEl.classList.contains('drop-before') ? 'before' : 'after';
            w?.call('moveNodeRelative', drag.id, parseInt(lineEl.dataset.bnode), pos);
            return finish();
          }

          const gap = e.target.closest('.addgap[data-gap-after]');
          if (gap && drag.kind === 'new') {
            e.preventDefault();
            const after = parseInt(gap.dataset.gapAfter);
            if (drag.type === 'container') w?.call('dropContainerAt', drag.cols, after);
            else w?.call('dropElementAt', drag.type, after);
            return finish();
          }

          const target = colTarget(e);
          if (!target) return finish();
          e.preventDefault();
          const { id, col } = colInfo(target);

          if (drag.kind === 'move') w?.call('moveNodeToColumn', drag.id, id, col);
          else w?.call('dropInto', drag.type, id, col, drag.cols);
          finish();
        });

        document.addEventListener('dragend', () => { clear(); drag = null; });

        // Floating toolbar for child elements: drag handle / duplicate / delete.
        // Lazy lookups + delegation: the toolbar div renders after this script.
        const tools = () => document.getElementById('el-tools');
        let toolsFor = null;
        const hideTools = () => { tools()?.classList.remove('show'); toolsFor = null; };

        document.addEventListener('mouseover', e => {
          if (e.target.closest('#el-tools')) return;
          const t = tools();
          const pf = document.querySelector('.page-frame');
          const el = e.target.closest('.page-frame [data-bnode][draggable]');
          pf?.classList.toggle('child-hover', !!el); // suppress section chip over children
          if (el && t) {
            toolsFor = parseInt(el.dataset.bnode);
            t.classList.toggle('is-container', !!el.dataset.bcontainer);
            const r = el.getBoundingClientRect();
            t.style.left = Math.max(r.left, 60) + 'px';
            t.style.top = (r.top - 12) + 'px';
            t.classList.add('show');
          } else {
            hideTools();
          }
        });
        document.addEventListener('scroll', hideTools, true);

        document.addEventListener('click', e => {
          if (e.target.closest('#elt-type')) {
            if (toolsFor) wire()?.call('selectNode', toolsFor);
          } else if (e.target.closest('#elt-dup')) {
            if (toolsFor) wire()?.call('duplicateNode', toolsFor);
            hideTools();
          } else if (e.target.closest('#elt-del')) {
            if (toolsFor && confirm('Delete this element?')) wire()?.call('deleteNode', toolsFor);
            hideTools();
          }
        });

        // Instant style preview: apply unit/side/color values as inline CSS
        // on the selected node; the server render confirms and replaces it.
        const CSSMAP = {
          color: 'color', background: 'background', font_size: 'font-size',
          font_weight: 'font-weight', line_height: 'line-height',
          letter_spacing: 'letter-spacing', text_transform: 'text-transform',
          text_align: 'text-align', width: 'width', max_width: 'max-width',
          height: 'height', min_height: 'min-height', gap: 'gap',
          object_fit: 'object-fit', border_style: 'border-style',
          border_color: 'border-color', margin: 'margin', padding: 'padding',
          border_width: 'border-width', border_radius: 'border-radius',
        };
        const CORNERS = { top: 'border-top-left-radius', right: 'border-top-right-radius',
                          bottom: 'border-bottom-right-radius', left: 'border-bottom-left-radius' };
        const unitOf = inp => {
          const sel = (inp.closest('.unit-wrap') || inp.closest('[data-sides-wrap]'))?.querySelector('.unit-sel');
          return sel ? sel.value : 'px';
        };
        const selectedNode = () => {
          const id = window.Livewire.all()[0]?.$wire?.selectedId;
          return id ? document.querySelector(`.page-frame [data-bnode="${id}"]`) : null;
        };
        window.__liveColor = (key, val) => {
          const node = selectedNode();
          if (node && CSSMAP[key]) node.style.setProperty(CSSMAP[key], val);
        };
        const liveApply = inp => {
          const key = inp.dataset.livecss;
          const node = selectedNode();
          if (!node) return;

          if (key === 'widths') {
            const vals = [...inp.closest('.wgrid').querySelectorAll('.wnum')].map(i => i.value || 0);
            node.style.gridTemplateColumns = vals.map(v => v + 'fr').join(' ');
            return;
          }
          if (key === 'element_gap') {
            node.querySelectorAll(':scope > .bcol').forEach(c => c.style.gap = inp.value + unitOf(inp));
            return;
          }

          const isNum = inp.type === 'number';
          const val = inp.value === '' ? '' : inp.value + (isNum ? unitOf(inp) : '');
          const side = inp.dataset.side;

          if (side) {
            const prop = key === 'border_radius' ? CORNERS[side]
              : key === 'border_width' ? `border-${side}-width`
              : `${CSSMAP[key]}-${side}`;
            node.style.setProperty(prop, val);
            return;
          }
          if (CSSMAP[key]) node.style.setProperty(CSSMAP[key], val);
        };

        document.addEventListener('input', e => {
          const inp = e.target.closest('[data-livecss]');
          if (inp) liveApply(inp);
        });
        document.addEventListener('change', e => {
          if (!e.target.closest('[data-livecss-unit]')) return;
          (e.target.closest('.unit-wrap') || e.target.closest('[data-sides-wrap]'))
            ?.querySelectorAll('[data-livecss]').forEach(liveApply);
        });

        // Instant typing: mirror content-field keystrokes straight into the
        // canvas DOM; the debounced server render confirms right behind it.
        document.addEventListener('input', e => {
          const inp = e.target.closest('[data-mirror]');
          if (!inp) return;
          const id = window.Livewire.all()[0]?.$wire?.selectedId;
          if (!id) return;
          const node = document.querySelector(`.page-frame [data-bnode="${id}"]`);
          if (!node) return;
          if (inp.dataset.mirrorMode === 'html') {
            node.innerHTML = inp.value;
          } else {
            let target = node;
            while (target.children.length === 1 && target.children[0].childElementCount === 0
                   && ['A', 'SPAN'].includes(target.children[0].tagName)) {
              target = target.children[0];
            }
            target.textContent = inp.value;
          }
        });
      })();
    </script>

    <!-- floating element toolbar (positioned by JS on hover) -->
    <div id="el-tools">
      <span id="elt-type" title="Select">
        <svg data-t="container" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="12" y1="5" x2="12" y2="19"/></svg>
        <svg data-t="element" viewBox="0 0 24 24"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
      </span>
      <button id="elt-drag" draggable="true" title="Drag to move">
        <svg viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.2"/><circle cx="15" cy="6" r="1.2"/><circle cx="9" cy="12" r="1.2"/><circle cx="15" cy="12" r="1.2"/><circle cx="9" cy="18" r="1.2"/><circle cx="15" cy="18" r="1.2"/></svg>
      </button>
      <button id="elt-dup" title="Duplicate">
        <svg viewBox="0 0 24 24"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
      </button>
      <button id="elt-del" title="Delete">
        <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M6 6l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14"/></svg>
      </button>
    </div>

    <!-- NAVIGATOR -->
    @if ($showNav)
      <div class="navigator open">
        <div class="nav-head">Navigator
          <button wire:click="toggleNav"><svg class="ic" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <div class="nav-list">
          @foreach ($tree as $row)
            <div class="nav-row {{ $selectedId === $row['id'] ? 'sel' : '' }}"
                 style="padding-left:{{ 8 + $row['depth'] * 16 }}px;{{ $row['depth'] > 0 ? 'font-weight:500' : '' }}"
                 wire:key="nav-{{ $row['id'] }}" wire:click="selectNode({{ $row['id'] }})">
              {{ $row['label'] }}
              <button class="eye" wire:click.stop="moveNode({{ $row['id'] }}, 'up')" title="Move up"><svg class="ic" viewBox="0 0 24 24" style="width:12px;height:12px"><path d="m18 15-6-6-6 6"/></svg></button>
              <button class="eye" style="margin-left:0" wire:click.stop="moveNode({{ $row['id'] }}, 'down')" title="Move down"><svg class="ic" viewBox="0 0 24 24" style="width:12px;height:12px"><path d="m6 9 6 6 6-6"/></svg></button>
              <button class="eye" style="margin-left:0" wire:click.stop="toggleVisible({{ $row['id'] }})" title="Toggle visibility">
                <svg class="ic" viewBox="0 0 24 24" style="width:12px;height:12px">@if ($row['visible'])<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>@else<path d="M17.94 17.94A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a17.6 17.6 0 0 1 4.06-4.94"/><line x1="2" y1="2" x2="22" y2="22"/>@endif</svg>
              </button>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </main>
</div>
