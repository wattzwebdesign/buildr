<div class="dash">
  <aside class="side">
    <div class="side-logo">
      <svg viewBox="0 0 24 24"><path d="M13 2 4.5 13.5H11L9.5 22 19 10h-6.5z"/></svg>
      Buildr
    </div>
    <div class="side-site">
      <span class="dotlive"></span> {{ \Buildr\Models\SiteSetting::get('name', config('app.name')) }}
    </div>
    <nav>
      <a href="{{ route('buildr.pages') }}" class="on"><svg class="ic" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>Pages</a>
    </nav>
    <div class="side-foot" style="display:flex;align-items:center;gap:8px">
      buildr {{ \Composer\InstalledVersions::getPrettyVersion('buildr/buildr') ?? 'dev' }}
      <button data-theme-toggle title="Light / dark mode" style="margin-left:auto;width:26px;height:26px;display:grid;place-items:center;border-radius:7px;color:inherit">
        <svg class="ic" viewBox="0 0 24 24" style="width:14px;height:14px"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>
    </div>
  </aside>

  <main class="main">
    <div class="main-head">
      <h1>Pages</h1>
      <span class="count">{{ $pages->count() }} {{ Str::plural('PAGE', $pages->count()) }}</span>
      <form wire:submit="createPage" style="margin-left:auto;display:flex;flex-direction:column;gap:4px;align-items:flex-end">
        <div style="display:flex;gap:8px">
          <input class="in" style="width:210px @error('newTitle');border-color:var(--danger)@enderror" placeholder="New page title…" wire:model="newTitle">
          <button type="submit" class="btn-primary" style="margin-left:0">
            <span wire:loading.remove wire:target="createPage" style="display:flex;align-items:center;gap:7px">
              <svg class="ic" viewBox="0 0 24 24" style="width:14px;height:14px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>New Page
            </span>
            <span wire:loading wire:target="createPage">Creating…</span>
          </button>
        </div>
        @error('newTitle')<span style="font-size:11px;color:var(--danger)">{{ $message }}</span>@enderror
      </form>
    </div>

    <div class="searchbar">
      <svg class="ic" viewBox="0 0 24 24" style="width:14px;height:14px"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
      <input placeholder="Search pages…" wire:model.live.debounce.300ms="search">
    </div>

    <div class="pagelist">
      <div class="pl-head"><span>Page</span><span>URL</span><span>Status</span><span>Sections</span><span>Updated</span><span></span></div>

      @forelse ($pages as $page)
        <div class="pl-row" wire:key="page-{{ $page->id }}">
          <span class="pl-name">{{ $page->title }}</span>
          <span class="pl-slug">/{{ $page->slug }}</span>
          <button class="pill {{ $page->isPublished() ? 'live' : 'draft' }}"
                  wire:click="togglePublish({{ $page->id }})"
                  title="Click to {{ $page->isPublished() ? 'unpublish' : 'publish' }}">
            {{ $page->isPublished() ? 'Published' : 'Draft' }}
          </button>
          <span class="pl-secs">{{ $page->nodes_count }}</span>
          <span class="pl-upd">{{ $page->updated_at->diffForHumans(short: true) }}</span>
          <span style="justify-self:end;display:flex;gap:6px">
            @if ($page->isPublished())
              <a class="pl-edit" href="/{{ $page->slug }}" target="_blank" title="View live">View</a>
            @endif
            <a class="pl-edit" href="#" title="Editor — next stage">Edit</a>
            <button class="pl-edit" style="color:var(--danger)"
                    wire:click="deletePage({{ $page->id }})"
                    wire:confirm="Delete “{{ $page->title }}” and all its content?">✕</button>
          </span>
        </div>
      @empty
        <div class="pl-row"><span class="pl-name" style="color:var(--muted)">No pages yet — create one above.</span></div>
      @endforelse
    </div>
  </main>
</div>
