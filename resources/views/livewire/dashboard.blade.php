<div class="dash">
  @include('buildr::livewire.partials.sidenav', ['active' => 'pages', 'updateAvailable' => $updateAvailable])

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
            <a class="pl-edit" href="{{ route('buildr.edit', $page) }}">Edit</a>
            <button class="pl-edit" style="color:var(--danger)"
                    @click="bConfirm(@js('Delete “'.$page->title.'” and all its content?'), { danger: true }).then(ok => ok && $wire.deletePage({{ $page->id }}))">✕</button>
          </span>
        </div>
      @empty
        <div class="pl-row"><span class="pl-name" style="color:var(--muted)">No pages yet — create one above.</span></div>
      @endforelse
    </div>
  </main>
</div>
