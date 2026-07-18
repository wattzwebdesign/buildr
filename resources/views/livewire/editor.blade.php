<div class="app" x-data="{ dev: 'desktop' }">
<style>body{overflow:hidden}</style>

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
      <button class="ph-btn" data-theme-toggle title="Light / dark mode" style="display:grid;place-items:center">
        <svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>
    </div>

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
        <div class="fld-hint">Click a section in the canvas to edit it.</div>
      @endif

      @foreach ($fields as $field)
        <div class="fld" wire:key="fld-{{ $selectedId }}-{{ $tab }}-{{ $field['key'] }}">
          <div class="fld-label">{{ $field['label'] }}</div>

          @if ($tab === 'content' && in_array($field['type'], ['text']))
            <input class="in" wire:model.blur="content.{{ $field['key'] }}">
          @elseif ($tab === 'content' && in_array($field['type'], ['textarea', 'richtext']))
            <textarea class="in" rows="4" wire:model.blur="content.{{ $field['key'] }}"></textarea>
          @elseif ($tab === 'content' && $field['type'] === 'select' && !empty($field['options']))
            <select class="in" wire:model.change="content.{{ $field['key'] }}">
              @foreach ($field['options'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
              @endforeach
            </select>
          @elseif ($tab === 'content' && $field['type'] === 'toggle')
            <div class="togglerow">
              <span>{{ $field['label'] }}</span>
              <input type="checkbox" wire:model.change="content.{{ $field['key'] }}">
            </div>
          @else
            <div class="fld-hint" style="padding:9px 11px;border:1px dashed var(--panel-line);border-radius:8px">
              {{ ucfirst($field['type']) }} control — wired in the next stage
            </div>
          @endif

          @if (!empty($field['help']))
            <div class="fld-hint">{!! $field['help'] !!}</div>
          @endif
        </div>
      @endforeach
    </div>

    <div class="panel-foot">
      <span style="font-family:'JetBrains Mono',monospace;font-size:9.5px;color:var(--chrome-muted);padding:0 8px">
        {{ $page->isPublished() ? 'PUBLISHED' : 'DRAFT' }}
      </span>
      <button class="publish" wire:click="publish" style="margin-left:auto">
        <span wire:loading.remove wire:target="publish">{{ $page->isPublished() ? 'Update' : 'Publish' }}</span>
        <span wire:loading wire:target="publish">Saving…</span>
      </button>
    </div>
  </aside>

  <!-- ============ CANVAS ============ -->
  <main class="canvas" :class="{ 'dev-tablet': dev === 'tablet', 'dev-mobile': dev === 'mobile' }">
    <div class="canvas-bar">
      <div class="crumb">
        <span class="site">{{ \Buildr\Models\SiteSetting::get('name', config('app.name')) }}</span>
        <span class="sep">/</span>
        <span>{{ $page->title }}</span>
        <span class="status mono">{{ strtoupper($page->updated_at->diffForHumans(short: true)) }}</span>
      </div>
      <div class="devices">
        <button :class="{ on: dev === 'desktop' }" @click="dev = 'desktop'" title="Desktop">
          <svg class="ic" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        </button>
        <button :class="{ on: dev === 'tablet' }" @click="dev = 'tablet'" title="Tablet">
          <svg class="ic" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18.01"/></svg>
        </button>
        <button :class="{ on: dev === 'mobile' }" @click="dev = 'mobile'" title="Mobile">
          <svg class="ic" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18.01"/></svg>
        </button>
      </div>
      @if ($page->isPublished())
        <a href="/{{ $page->slug }}" target="_blank" style="color:var(--accent);font-size:12px;font-weight:600;text-decoration:none">View live ↗</a>
      @endif
    </div>

    <div class="canvas-scroll">
      <div class="page-frame">
        {!! '<style>'.$rendered['css'].'</style>' !!}
        @forelse ($rendered['roots'] as $root)
          <section class="pv-sec {{ $selectedId === $root['id'] ? 'sel' : '' }}"
                   wire:key="sec-{{ $root['id'] }}"
                   wire:click="selectNode({{ $root['id'] }})">
            <div class="sec-tools"><span class="lbl">{{ $root['label'] }}</span></div>
            {!! $root['html'] !!}
          </section>
        @empty
          <div style="padding:80px 40px;text-align:center;color:var(--muted);font-size:14px">
            Empty page — the section library arrives in the next stage.
          </div>
        @endforelse
      </div>
    </div>
  </main>
</div>
