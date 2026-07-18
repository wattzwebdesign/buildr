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
.bcol-ph{
  border:1.5px dashed rgba(150,150,160,.5);border-radius:8px;min-height:110px;
  display:grid;place-items:center;font-family:Archivo,sans-serif;font-size:12px;
  color:#8b8f98;cursor:pointer;transition:.15s;margin:6px 0;
}
.bcol-ph:hover,.bcol-ph.drop-hot{border-color:var(--accent);color:var(--accent);background:rgba(255,178,0,.06)}
[data-bcontainer].drop-hot{outline:2px dashed var(--accent);outline-offset:-2px}
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
            <button class="el-card" x-show="!q || '{{ strtolower($label) }}'.includes(q.toLowerCase())"
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

    <div class="panel-foot">
      <button class="pf-btn {{ $showNav ? 'on' : '' }}" wire:click="toggleNav" title="Navigator" style="display:grid;place-items:center">
        <svg class="ic" viewBox="0 0 24 24"><path d="m12 2 9 5-9 5-9-5 9-5z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></svg>
      </button>
      <span style="font-family:'JetBrains Mono',monospace;font-size:9.5px;color:var(--chrome-muted);padding:0 8px">
        <span wire:loading.remove>{{ $page->isPublished() ? 'PUBLISHED' : 'DRAFT' }}</span>
        <span wire:loading style="color:var(--accent)">SAVING…</span>
      </span>
      <button class="publish" wire:click="publish" style="margin-left:auto">
        <span wire:loading.remove wire:target="publish">{{ $page->isPublished() ? 'Update' : 'Publish' }}</span>
        <span wire:loading wire:target="publish">Saving…</span>
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
             if (ph) { $wire.openLibraryFor(parseInt(ph.dataset.bcolph)); return; }
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
            <button data-tree title="Add section at end" wire:click="openLibrary">
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
        const hot = (el, on) => el && el.classList.toggle('drop-hot', on);
        let current = null;

        document.addEventListener('dragstart', e => {
          const card = e.target.closest('.el-card[data-etype]');
          if (card) e.dataTransfer.setData('text/plain', 'buildr:' + card.dataset.etype);
        });

        document.addEventListener('dragover', e => {
          const target = e.target.closest('[data-bcolph], [data-bcontainer]');
          if (!target) return;
          e.preventDefault();
          if (current !== target) { hot(current, false); current = target; hot(current, true); }
        });

        document.addEventListener('dragleave', e => {
          if (current && !current.contains(e.relatedTarget)) { hot(current, false); current = null; }
        });

        document.addEventListener('drop', e => {
          const target = e.target.closest('[data-bcolph], [data-bcontainer]');
          hot(current, false); current = null;
          if (!target) return;
          const payload = e.dataTransfer.getData('text/plain');
          if (!payload.startsWith('buildr:')) return;
          e.preventDefault();
          const type = payload.slice(7);
          const id = parseInt(target.dataset.bcolph || target.dataset.bcontainer);
          const comp = window.Livewire.all()[0];
          if (comp) comp.call('dropElement', type, id);
        });
      })();
    </script>

    <!-- NAVIGATOR -->
    @if ($showNav)
      <div class="navigator open">
        <div class="nav-head">Navigator
          <button wire:click="toggleNav"><svg class="ic" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <div class="nav-list">
          @foreach ($tree as $root)
            <div class="nav-row {{ $selectedId === $root['id'] ? 'sel' : '' }}" wire:key="nav-{{ $root['id'] }}" wire:click="selectNode({{ $root['id'] }})">
              {{ $root['label'] }}
              <button class="eye" wire:click.stop="moveNode({{ $root['id'] }}, 'up')" title="Move up"><svg class="ic" viewBox="0 0 24 24" style="width:12px;height:12px"><path d="m18 15-6-6-6 6"/></svg></button>
              <button class="eye" style="margin-left:0" wire:click.stop="moveNode({{ $root['id'] }}, 'down')" title="Move down"><svg class="ic" viewBox="0 0 24 24" style="width:12px;height:12px"><path d="m6 9 6 6 6-6"/></svg></button>
              <button class="eye" style="margin-left:0" wire:click.stop="toggleVisible({{ $root['id'] }})" title="Toggle visibility">
                <svg class="ic" viewBox="0 0 24 24" style="width:12px;height:12px">@if ($root['visible'])<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>@else<path d="M17.94 17.94A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a17.6 17.6 0 0 1 4.06-4.94"/><line x1="2" y1="2" x2="22" y2="22"/>@endif</svg>
              </button>
            </div>
            @foreach ($root['children'] as $child)
              <div class="nav-row {{ $selectedId === $child['id'] ? 'sel' : '' }}" style="padding-left:26px;font-weight:500"
                   wire:key="nav-{{ $child['id'] }}" wire:click="selectNode({{ $child['id'] }})">
                {{ $child['label'] }}
                <button class="eye" wire:click.stop="toggleVisible({{ $child['id'] }})" title="Toggle visibility">
                  <svg class="ic" viewBox="0 0 24 24" style="width:12px;height:12px">@if ($child['visible'])<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>@else<path d="M17.94 17.94A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a17.6 17.6 0 0 1 4.06-4.94"/><line x1="2" y1="2" x2="22" y2="22"/>@endif</svg>
                </button>
              </div>
            @endforeach
          @endforeach
        </div>
      </div>
    @endif
  </main>
</div>
