@php
    $icon = fn (string $name) => match ($name) {
        'layout' => '<rect x="3" y="5" width="18" height="14" rx="2"/><line x1="12" y1="5" x2="12" y2="19"/>',
        'heading' => '<path d="M6 4v16M18 4v16M6 12h12"/>',
        'text' => '<path d="M4 7V5h16v2M12 5v14M9 19h6"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-4.5-4.5L7 21"/>',
        'button' => '<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M8 12h8"/>',
        'divider' => '<line x1="3" y1="12" x2="21" y2="12" stroke-dasharray="3 3"/>',
        'spacer' => '<path d="M12 5v14M8 8l4-3 4 3M8 16l4 3 4-3"/>',
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
        <button class="lib-back" wire:click="closeLibrary">
          <svg class="ic" viewBox="0 0 24 24"><path d="m12 19-7-7 7-7M5 12h14"/></svg> Back to editing
        </button>
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

        @foreach ($fields as $field)
          @include('buildr::livewire.partials.control', ['field' => $field, 'tab' => $tab, 'device' => $device])
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
      <div class="page-frame"
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
        {!! '<style>'.$rendered['css'].'</style>' !!}

        @forelse ($rendered['roots'] as $i => $root)
          <div class="addgap">
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
          <div style="padding:80px 40px;text-align:center;color:var(--muted);font-size:14px">
            Empty page — add your first section from the library.
            <div style="margin-top:14px"><button data-tree class="btn-primary" style="margin:0 auto" wire:click="openLibrary">Open library</button></div>
          </div>
        @endforelse

        @if (count($rendered['roots']))
          <div class="addgap" style="height:22px">
            <button data-tree title="Add section at end" wire:click="openLibrary({{ end($rendered['roots'])['id'] }})">
              <svg class="ic" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
          </div>
        @endif
      </div>
    </div>

    <script data-navigate-once>
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

          // moving: hovering another element shows a before/after line
          if (drag.kind === 'move') {
            const el = e.target.closest('.page-frame [data-bnode][draggable]');
            if (el && parseInt(el.dataset.bnode) !== drag.id) {
              e.preventDefault();
              const rect = el.getBoundingClientRect();
              const before = e.clientY < rect.top + rect.height / 2;
              if (lineEl !== el) { clear(); lineEl = el; }
              el.classList.toggle('drop-before', before);
              el.classList.toggle('drop-after', !before);
              return;
            }
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
          if (e.target.closest('#elt-dup')) {
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
