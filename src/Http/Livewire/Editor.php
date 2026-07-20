<?php

namespace Buildr\Http\Livewire;

use Buildr\Fields\Field;
use Buildr\Models\Page;
use Buildr\Models\PageNode;
use Buildr\Render\PageRenderer;
use Buildr\Support\ElementRegistry;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('buildr::admin.layout')]
class Editor extends Component
{
    use \Livewire\WithFileUploads;

    public $upload = null;

    /** Settings path (e.g. settings.content.src) the next upload fills. */
    public string $mediaTarget = '';

    public function updatedUpload(): void
    {
        $this->validate(['upload' => 'image|max:8192']);

        $disk = config('buildr.media_disk', 'public');
        $path = $this->upload->store('buildr', $disk);

        \Buildr\Models\Media::create([
            'path' => $path,
            'name' => $this->upload->getClientOriginalName(),
            'mime' => $this->upload->getMimeType(),
            'size' => $this->upload->getSize(),
        ]);

        if ($this->mediaTarget !== '') {
            $url = \Illuminate\Support\Facades\Storage::disk($disk)->url($path);
            data_set($this, $this->mediaTarget, $url);
            if (str_starts_with($this->mediaTarget, 'pageForm')) {
                $this->pageDirty = true;
            } else {
                $this->persistTab(explode('.', $this->mediaTarget)[1] ?? 'content');
            }
        }

        $this->upload = null;
    }

    public Page $page;

    public ?int $selectedId = null;

    public string $tab = 'content';

    /** Device being previewed AND edited — responsive fields write to this key. */
    public string $device = 'desktop';

    /** Selected node's settings per tab, normalized to each field's shape. */
    public array $settings = ['content' => [], 'style' => [], 'advanced' => []];

    public function mount(Page $page): void
    {
        $this->page = $page;
        $this->upgradeLegacyColumns();

        $first = $page->rootNodes()->first();
        if ($first) {
            $this->selectNode($first->id);
        }

        // Home view is the element picker, Elementor-style — clicking
        // anything in the canvas switches to its edit panel.
        $this->view = 'library';
    }

    /**
     * One-time upgrade: multi-column containers built before columns became
     * real nodes hold elements directly (bucketed by _col). Convert each
     * bucket into a proper column container so every column is stylable.
     */
    private function upgradeLegacyColumns(): void
    {
        $changed = false;

        foreach ($this->draftNodes()->where('type', 'container')->get() as $container) {
            $cols = count($container->data['content']['widths'] ?? [100]);
            if ($cols < 2) {
                continue;
            }

            $children = $container->children()->orderBy('sort')->get();

            if ($children->isEmpty()) {
                $this->makeColumns($container, $cols);
                $changed = true;

                continue;
            }

            if (! $children->contains(fn ($child) => $child->type !== 'container')) {
                continue; // already column containers
            }

            $buckets = array_fill(0, $cols, []);
            foreach ($children->values() as $i => $child) {
                $col = min((int) ($child->data['content']['_col'] ?? ($i % $cols)), $cols - 1);
                $buckets[$col][] = $child;
            }

            foreach ($buckets as $col => $bucket) {
                if (count($bucket) === 1 && $bucket[0]->type === 'container') {
                    $bucket[0]->update(['sort' => $col]);

                    continue;
                }

                $column = $this->page->nodes()->create([
                    'type' => 'container',
                    'parent_id' => $container->id,
                    'sort' => $col,
                    'data' => $this->containerData(1, $col),
                ]);

                foreach ($bucket as $j => $child) {
                    $data = $child->data ?? [];
                    $data['content']['_col'] = 0;
                    $child->update(['parent_id' => $column->id, 'sort' => $j, 'data' => $data]);
                }
            }

            $changed = true;
        }

        if ($changed) {
            $this->page->touch();
        }
    }

    public function selectNode(int $id): void
    {
        $this->selectedId = $id;
        $this->tab = 'content';
        $this->view = 'edit';
        $this->loadSettings();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['content', 'style', 'advanced'], true)) {
            $this->tab = $tab;
        }
    }

    public function setDevice(string $device): void
    {
        if (in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $this->device = $device;
        }
    }

    public function publish(): void
    {
        \Buildr\Support\Publisher::publish($this->page);
        $this->page->refresh();
    }

    public function discardDraft(): void
    {
        $this->pushHistory();
        \Buildr\Support\Publisher::discardDraft($this->page);
        $this->page->refresh();
        $first = $this->draftRoots()->orderBy('sort')->first();
        $first ? $this->selectNode($first->id) : $this->clearSelection();
    }

    /* ---------- undo / redo (action-level, session-scoped) ---------- */

    /** Per-request guard so nested mutators produce one undo step. */
    private bool $historyPushed = false;

    private function historyKey(string $stack): string
    {
        return "buildr.history.{$this->page->id}.".session()->getId().".{$stack}";
    }

    /**
     * Snapshot the draft tree before a mutation. Rapid edits sharing a
     * signature (typing in one node/tab) coalesce into a single undo step.
     */
    private function pushHistory(?string $signature = null): void
    {
        if ($this->historyPushed) {
            return;
        }
        $this->historyPushed = true;

        $undo = cache()->get($this->historyKey('undo'), []);
        $lastKey = $undo === [] ? null : array_key_last($undo);

        if ($signature !== null && $lastKey !== null
            && ($undo[$lastKey]['sig'] ?? null) === $signature
            && time() - ($undo[$lastKey]['at'] ?? 0) < 3) {
            $undo[$lastKey]['at'] = time();
            cache()->put($this->historyKey('undo'), $undo, now()->addHours(6));

            return;
        }

        $undo[] = ['tree' => \Buildr\Support\TreeSnapshot::capture($this->page), 'sig' => $signature, 'at' => time()];
        cache()->put($this->historyKey('undo'), array_slice($undo, -50), now()->addHours(6));
        cache()->forget($this->historyKey('redo'));
    }

    public function undo(): void
    {
        $this->shiftHistory('undo', 'redo');
    }

    public function redo(): void
    {
        $this->shiftHistory('redo', 'undo');
    }

    private function shiftHistory(string $from, string $to): void
    {
        $source = cache()->get($this->historyKey($from), []);
        $current = \Buildr\Support\TreeSnapshot::capture($this->page);

        // skip no-op entries (a mutator that early-returned after pushing)
        do {
            $entry = array_pop($source);
        } while ($entry !== null && $entry['tree'] == $current);

        cache()->put($this->historyKey($from), $source, now()->addHours(6));
        if ($entry === null) {
            return;
        }

        $dest = cache()->get($this->historyKey($to), []);
        $dest[] = ['tree' => $current, 'sig' => null, 'at' => time()];
        cache()->put($this->historyKey($to), array_slice($dest, -50), now()->addHours(6));
        $this->historyPushed = true; // restoring must not re-push

        \Buildr\Support\TreeSnapshot::restore($this->page, $entry['tree']);
        $this->page->touch();
        $this->clearSelection();
    }

    public function restoreRevision(int $revisionId): void
    {
        $this->pushHistory();
        $revision = $this->page->revisions()->whereKey($revisionId)->first();
        if (! $revision) {
            return;
        }

        \Buildr\Support\TreeSnapshot::restore($this->page, $revision->snapshot, asDraft: true);
        $this->page->touch(); // restored draft differs from published → dirty
        $this->view = 'edit';
        $first = $this->draftRoots()->orderBy('sort')->first();
        $first ? $this->selectNode($first->id) : $this->clearSelection();
    }

    /* ---------- library + tree operations ---------- */

    /** 'edit' or 'library' (the sidebar block library). */
    public string $view = 'edit';

    /** Root id to insert after; 0 = start of page; null = append at end. */
    public ?int $insertAfter = null;

    public bool $showNav = false;

    public function openLibrary(?int $afterRootId = null): void
    {
        $this->view = 'library';
        $this->insertAfter = $afterRootId;
    }

    public function closeLibrary(): void
    {
        $this->view = 'edit';
        $this->insertAfter = null;
    }

    /** Clicking an empty column: target that container, open the library. */
    public function openLibraryFor(int $containerId): void
    {
        $node = $this->draftNodes()->whereKey($containerId)->first();
        if (! $node || $node->type !== 'container') {
            return;
        }

        $this->selectedId = $containerId;
        $this->loadSettings();
        $this->view = 'library';
        $this->insertAfter = null;
    }

    /** Drag-and-drop from the library onto a specific container column. */
    public function dropInto(string $type, int $containerId, int $col = 0, int $cols = 1): void
    {
        $this->pushHistory();
        $container = $this->draftNodes()->whereKey($containerId)->first();
        if (! $container || $container->type !== 'container') {
            return;
        }

        if ($type === 'container') {
            $node = $this->page->nodes()->create([
                'type' => 'container',
                'parent_id' => $container->id,
                'sort' => $container->children()->count(),
                'data' => $this->containerData($cols, $col),
            ]);
            $this->makeColumns($node, $cols);
            $this->resequence($container->id);
            $this->page->touch();
            $this->selectNode($node->id);

            return;
        }

        $this->selectedId = $containerId;
        $this->addElement($type, $col);
    }

    /** BC alias — old drop path without a column. */
    public function dropElement(string $type, int $containerId, int $cols = 1): void
    {
        $this->pushHistory();
        $this->dropInto($type, $containerId, 0, $cols);
    }

    /** Move an existing node before/after another node (drag reorder). */
    public function moveNodeRelative(int $nodeId, int $targetId, string $position): void
    {
        $this->pushHistory();
        $node = $this->draftNodes()->whereKey($nodeId)->first();
        $target = $this->draftNodes()->whereKey($targetId)->first();

        if (! $node || ! $target || $node->id === $target->id) {
            return;
        }
        if ($target->parent_id && $this->containsNode($node, $target->parent_id)) {
            return; // can't move a container inside itself
        }
        if (! $target->parent_id && $node->parent_id === null && $node->type !== 'container') {
            return; // bare elements can't become page roots
        }

        $oldParent = $node->parent_id;
        $sort = $target->sort + ($position === 'after' ? 1 : 0);

        $this->draftNodes()->where('parent_id', $target->parent_id)->where('sort', '>=', $sort)->increment('sort');

        $data = $node->data ?? [];
        if ($target->parent_id) {
            $data['content']['_col'] = $target->data['content']['_col'] ?? 0;
        }
        $node->update(['parent_id' => $target->parent_id, 'sort' => $sort, 'data' => $data]);

        $this->resequence($oldParent);
        $this->resequence($target->parent_id);
        $this->page->touch();
        $this->selectNode($node->id);
    }

    /** Move an existing node into a container column (append). */
    public function moveNodeToColumn(int $nodeId, int $containerId, int $col): void
    {
        $this->pushHistory();
        $node = $this->draftNodes()->whereKey($nodeId)->first();
        $container = $this->draftNodes()->whereKey($containerId)->first();

        if (! $node || ! $container || $container->type !== 'container' || $node->id === $container->id) {
            return;
        }
        if ($this->containsNode($node, $containerId)) {
            return;
        }

        $oldParent = $node->parent_id;
        $data = $node->data ?? [];
        $data['content']['_col'] = $col;
        $node->update([
            'parent_id' => $containerId,
            'sort' => ($container->children()->max('sort') ?? -1) + 1,
            'data' => $data,
        ]);

        $this->resequence($oldParent);
        $this->resequence($containerId);
        $this->page->touch();
        $this->selectNode($node->id);
    }

    /** Is $possibleDescendantId inside $node's subtree (or the node itself)? */
    private function containsNode(PageNode $node, int $possibleDescendantId): bool
    {
        $current = $this->draftNodes()->whereKey($possibleDescendantId)->first();
        while ($current) {
            if ($current->id === $node->id) {
                return true;
            }
            $current = $current->parent_id ? $this->draftNodes()->whereKey($current->parent_id)->first() : null;
        }

        return false;
    }

    public function toggleNav(): void
    {
        $this->showNav = ! $this->showNav;
    }

    /* ---------- site settings (globals) ---------- */

    public array $site = [];

    public function openSite(): void
    {
        $this->view = 'site';
        $this->siteDirty = false;
        $this->site = [
            'name' => \Buildr\Models\SiteSetting::get('name', ''),
            'phone' => \Buildr\Models\SiteSetting::get('phone', ''),
            'email' => \Buildr\Models\SiteSetting::get('email', ''),
            'address' => \Buildr\Models\SiteSetting::get('address', ''),
            'colors' => \Buildr\Models\SiteSetting::get('colors', [
                ['name' => 'Primary', 'value' => '#1f2933'],
                ['name' => 'Accent', 'value' => '#2563eb'],
            ]),
            'font_heading' => \Buildr\Models\SiteSetting::get('font_heading', ''),
            'font_heading_weight' => \Buildr\Models\SiteSetting::get('font_heading_weight', ''),
            'font_body' => \Buildr\Models\SiteSetting::get('font_body', ''),
            'font_body_weight' => \Buildr\Models\SiteSetting::get('font_body_weight', ''),
            'base_size' => \Buildr\Models\SiteSetting::get('base_size', ''),
        ];
    }

    /* ---------- page settings ---------- */

    public array $pageForm = [];

    public bool $pageDirty = false;

    public function openPage(): void
    {
        $this->view = 'page';
        $this->pageDirty = false;
        $this->pageForm = [
            'title' => $this->page->title,
            'slug' => $this->page->slug,
            'seo_title' => $this->page->seo_title,
            'seo_description' => $this->page->seo_description,
            'background' => array_merge(
                ['color' => '', 'image' => '', 'position' => '', 'attachment' => '', 'repeat' => '', 'size' => ''],
                $this->page->settings['background'] ?? []
            ),
        ];
    }

    public function updatedPageForm(): void
    {
        $this->pageDirty = true;
    }

    public function savePage(): void
    {
        $slug = \Illuminate\Support\Str::slug($this->pageForm['slug'] ?: $this->pageForm['title']) ?: $this->page->slug;
        $base = $slug;
        for ($i = 2; \Buildr\Models\Page::where('slug', $slug)->whereKeyNot($this->page->id)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        $this->page->update([
            'title' => trim($this->pageForm['title']) ?: $this->page->title,
            'slug' => $slug,
            'seo_title' => $this->pageForm['seo_title'] ?: null,
            'seo_description' => $this->pageForm['seo_description'] ?: null,
            'settings' => array_merge($this->page->settings ?? [], [
                'background' => $this->pageForm['background'] ?? [],
            ]),
        ]);

        $this->pageForm['slug'] = $slug;
        $this->pageDirty = false;
    }

    public function openHistory(): void
    {
        $this->view = 'history';
    }

    public bool $siteDirty = false;

    /** Edits only mark the panel dirty — nothing persists until Save. */
    public function updatedSite(): void
    {
        $this->siteDirty = true;
    }

    public function saveSite(): void
    {
        foreach ($this->site as $key => $value) {
            \Buildr\Models\SiteSetting::set($key, $value);
        }
        $this->page->touch(); // bust page cache so globals recompile
        $this->siteDirty = false;
    }

    public function addGlobalColor(): void
    {
        $this->site['colors'][] = ['name' => 'Color '.(count($this->site['colors']) + 1), 'value' => '#888888'];
        $this->siteDirty = true;
    }

    public function removeGlobalColor(int $index): void
    {
        unset($this->site['colors'][$index]);
        $this->site['colors'] = array_values($this->site['colors']);
        $this->siteDirty = true;
    }

    private const COL_WIDTHS = [1 => [100], 2 => [50, 50], 3 => [33, 33, 33], 4 => [25, 25, 25, 25]];

    /** New containers ship with 10px padding on every side (Elementor-style). */
    private function containerData(int $cols, int $col = 0): array
    {
        $sides = [];
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $sides[$side] = ['value' => 10, 'unit' => 'px'];
        }

        return [
            'content' => ['widths' => self::COL_WIDTHS[$cols] ?? [100], '_col' => $col] + $this->schemaDefaults('container'),
            'advanced' => ['padding' => $sides],
        ];
    }

    public function addContainer(int $cols): void
    {
        $this->pushHistory();
        $data = $this->containerData($cols);

        // A "+" gap picked a page-level spot — insert a root section there.
        // Otherwise: nest inside the selected container, or beside the
        // selected element inside its parent. No selection = root at end.
        $current = $this->insertAfter === null ? $this->node() : null;

        if ($current && $current->type === 'container') {
            $node = $this->page->nodes()->create([
                'type' => 'container',
                'parent_id' => $current->id,
                'sort' => $current->children()->count(),
                'data' => $data,
            ]);
            $this->resequence($current->id);
        } elseif ($current && $current->parent_id) {
            $sort = $current->sort + 1;
            $this->draftNodes()->where('parent_id', $current->parent_id)->where('sort', '>=', $sort)->increment('sort');
            $node = $this->page->nodes()->create([
                'type' => 'container',
                'parent_id' => $current->parent_id,
                'sort' => $sort,
                'data' => $data,
            ]);
            $this->resequence($current->parent_id);
        } else {
            $node = $this->page->nodes()->create([
                'type' => 'container',
                'sort' => $this->insertSort(),
                'data' => $data,
            ]);
            $this->resequence(null);
        }

        $this->makeColumns($node, $cols);
        $this->insertAfter = null; // one-shot page position
        $this->page->touch();
        $this->selectNode($node->id);
    }

    /** Drop a Layout card onto a page-level "+" gap. */
    public function dropContainerAt(int $cols, int $afterRootId): void
    {
        $this->pushHistory();
        $this->insertAfter = $afterRootId;
        $this->addContainer($cols);
    }

    /** Drop an element onto a page-level "+" gap: new container wraps it. */
    public function dropElementAt(string $type, int $afterRootId): void
    {
        $this->pushHistory();
        $this->insertAfter = $afterRootId;
        $this->addContainer(1);          // selects the new container
        $this->addElement($type);
    }

    /**
     * Elementor-style columns: a multi-column container gets one inner
     * container per track — each column is a real, stylable node.
     */
    private function makeColumns(PageNode $parent, int $cols): void
    {
        if ($cols < 2) {
            return;
        }

        for ($i = 0; $i < $cols; $i++) {
            $this->page->nodes()->create([
                'type' => 'container',
                'parent_id' => $parent->id,
                'sort' => $i,
                'data' => $this->containerData(1, $i),
            ]);
        }
    }

    public function addElement(string $type, ?int $col = null): void
    {
        $this->pushHistory();
        $registry = app(ElementRegistry::class);
        if (! $registry->has($type)) {
            return;
        }

        if ($type === 'container') {
            $this->addContainer(1);

            return;
        }

        // Elements live inside containers: use the selected container, the
        // selected element's parent, or a fresh container at the page end.
        $current = $this->node();
        if ($current && $current->type === 'container') {
            $parent = $current;
            $sort = $parent->children()->count();
            $col ??= 0;
        } elseif ($current && $current->parent_id) {
            $parent = $current->parent;
            $sort = $current->sort + 1;
            $col ??= $current->data['content']['_col'] ?? 0;
        } else {
            $parent = $this->page->nodes()->create([
                'type' => 'container',
                'sort' => ($this->draftRoots()->max('sort') ?? -1) + 1,
                'data' => $this->containerData(1),
            ]);
            $sort = 0;
            $col ??= 0;
        }

        $node = $this->page->nodes()->create([
            'type' => $type,
            'parent_id' => $parent->id,
            'sort' => $sort,
            'data' => ['content' => ['_col' => $col] + $this->schemaDefaults($type)],
        ]);

        $this->resequence($parent->id);
        $this->page->touch();
        $this->selectNode($node->id);
    }

    public function moveNode(int $id, string $dir): void
    {
        $this->pushHistory();
        $node = $this->draftNodes()->whereKey($id)->first();
        if (! $node) {
            return;
        }

        $siblings = $this->page->nodes()
            ->where('parent_id', $node->parent_id)
            ->orderBy('sort')->get()->values();

        $index = $siblings->search(fn ($n) => $n->id === $node->id);
        $swap = $dir === 'up' ? $index - 1 : $index + 1;

        if ($swap < 0 || $swap >= $siblings->count()) {
            return;
        }

        $other = $siblings[$swap];
        [$a, $b] = [$node->sort, $other->sort];
        $node->update(['sort' => $b]);
        $other->update(['sort' => $a]);
        $this->page->touch();
    }

    public function duplicateNode(int $id): void
    {
        $this->pushHistory();
        $node = $this->draftNodes()->whereKey($id)->first();
        if (! $node) {
            return;
        }

        $this->page->nodes()
            ->where('parent_id', $node->parent_id)
            ->where('sort', '>', $node->sort)
            ->increment('sort');

        $copy = $this->copyNode($node, $node->parent_id, $node->sort + 1);
        $this->page->touch();
        $this->selectNode($copy->id);
    }

    public function deleteNode(int $id): void
    {
        $this->pushHistory();
        $node = $this->draftNodes()->whereKey($id)->first();
        if (! $node) {
            return;
        }

        $parentId = $node->parent_id;
        $node->children()->get()->each(fn ($c) => $c->delete());
        $node->delete();
        $this->resequence($parentId);
        $this->page->touch();

        if ($this->selectedId === $id || ! $this->node()) {
            $fallback = $parentId ?: $this->draftRoots()->first()?->id;
            $fallback ? $this->selectNode($fallback) : $this->clearSelection();
        }
    }

    /* ---------- clipboard (right-click menu) ---------- */

    public ?int $clipboardId = null;

    public function copyToClipboard(int $id): void
    {
        if ($this->draftNodes()->whereKey($id)->exists()) {
            $this->clipboardId = $id;
        }
    }

    /** Paste the copied subtree as a sibling right after the target node. */
    public function pasteAfter(int $targetId): void
    {
        $this->pushHistory();
        $clip = $this->clipboardId ? $this->draftNodes()->whereKey($this->clipboardId)->first() : null;
        $target = $this->draftNodes()->whereKey($targetId)->first();

        if (! $clip || ! $target || $this->containsNode($clip, $targetId)) {
            return;
        }

        $this->draftNodes()->where('parent_id', $target->parent_id)
            ->where('sort', '>', $target->sort)->increment('sort');

        $copy = $this->copyNode($clip, $target->parent_id, $target->sort + 1);

        $data = $copy->data ?? [];
        $data['content']['_col'] = $target->data['content']['_col'] ?? 0;
        $copy->update(['data' => $data]);

        $this->resequence($target->parent_id);
        $this->page->touch();
        $this->selectNode($copy->id);
    }

    /** Apply the copied node's Style-tab settings to the target. */
    public function pasteStyleTo(int $targetId): void
    {
        $this->pushHistory();
        $clip = $this->clipboardId ? $this->draftNodes()->whereKey($this->clipboardId)->first() : null;
        $target = $this->draftNodes()->whereKey($targetId)->first();

        if (! $clip || ! $target) {
            return;
        }

        $data = $target->data ?? [];
        $data['style'] = $clip->data['style'] ?? [];
        $target->update(['data' => $data]);
        $this->page->touch();
        $this->selectNode($target->id);
    }

    public function resetStyle(int $id): void
    {
        $this->pushHistory();
        $node = $this->draftNodes()->whereKey($id)->first();
        if (! $node) {
            return;
        }

        $data = $node->data ?? [];
        $data['style'] = [];
        $node->update(['data' => $data]);
        $this->page->touch();
        $this->selectNode($id);
    }

    /** Custom node name shown in navigator/chips (stored as _label). */
    public function renameNode(int $id, string $name): void
    {
        $this->pushHistory();
        $node = $this->draftNodes()->whereKey($id)->first();
        if (! $node) {
            return;
        }

        $data = $node->data ?? [];
        $name = trim($name);
        if ($name === '') {
            unset($data['content']['_label']);
        } else {
            $data['content']['_label'] = mb_substr($name, 0, 60);
        }
        $node->update(['data' => $data]);
        $this->page->touch();
    }

    public function toggleVisible(int $id): void
    {
        $this->pushHistory();
        $node = $this->draftNodes()->whereKey($id)->first();
        $node?->update(['visible' => ! $node->visible]);
        $this->page->touch();
    }

    public function deselect(): void
    {
        $this->clearSelection();
    }

    private function clearSelection(): void
    {
        $this->selectedId = null;
        $this->settings = ['content' => [], 'style' => [], 'advanced' => []];
    }

    private function insertSort(): int
    {
        if ($this->insertAfter === null) {
            return ($this->draftRoots()->max('sort') ?? -1) + 1;
        }

        if ($this->insertAfter === 0) {
            $this->draftRoots()->increment('sort');

            return 0;
        }

        $after = $this->draftRoots()->whereKey($this->insertAfter)->first();
        $sort = ($after?->sort ?? -1) + 1;
        $this->draftRoots()->where('sort', '>=', $sort)->increment('sort');

        return $sort;
    }

    private function schemaDefaults(string $type): array
    {
        $defaults = [];
        foreach (app(ElementRegistry::class)->get($type)::contentFields() as $field) {
            if ($field->default !== null) {
                $defaults[$field->key] = $field->default;
            }
        }

        return $defaults;
    }

    private function copyNode(PageNode $node, ?int $parentId, int $sort): PageNode
    {
        $copy = $node->replicate();
        $copy->parent_id = $parentId;
        $copy->sort = $sort;
        $copy->save();

        foreach ($node->children as $i => $child) {
            $this->copyNode($child, $copy->id, $i);
        }

        return $copy;
    }

    private function resequence(?int $parentId): void
    {
        $children = $this->draftNodes()
            ->where('parent_id', $parentId)
            ->orderBy('sort')->get()->values();

        $children->each(fn ($n, $i) => $n->sort === $i || $n->update(['sort' => $i]));

        // When a container's children are all containers, they ARE its
        // columns — their column index must always follow their order,
        // so reordering columns swaps tracks instead of stacking them.
        if ($parentId === null || $children->isEmpty()) {
            return;
        }
        if ($children->contains(fn ($n) => $n->type !== 'container')) {
            return;
        }

        $parent = $this->draftNodes()->whereKey($parentId)->first();
        if (! $parent || $parent->type !== 'container') {
            return;
        }

        $cols = max(1, count($parent->data['content']['widths'] ?? [100]));
        foreach ($children as $i => $child) {
            $col = min($i, $cols - 1);
            if ((int) ($child->data['content']['_col'] ?? -1) !== $col) {
                $data = $child->data ?? [];
                $data['content']['_col'] = $col;
                $child->update(['data' => $data]);
            }
        }
    }

    /** Any panel edit: persist the edited tab's settings into the node. */
    public function updatedSettings(mixed $value, string $key): void
    {
        $this->persistTab(explode('.', $key, 2)[0]);
    }

    /** Container structure preset, e.g. "50,50". */
    public function setWidths(string $preset): void
    {
        $this->pushHistory();
        $widths = array_values(array_filter(array_map('intval', explode(',', $preset))));
        if ($widths === []) {
            return;
        }

        $this->settings['content']['widths'] = $widths;
        $this->persistTab('content');
    }

    public function addRepeaterItem(string $tab, string $key): void
    {
        $this->pushHistory();
        $node = $this->node();
        if (! $node) {
            return;
        }

        $item = [];
        foreach ($this->elementClass($node)::{$tab.'Fields'}() as $field) {
            if ($field->key === $key && $field->type === 'repeater') {
                foreach ($field->fields as $sub) {
                    $item[$sub->key] = $sub->default;
                }
            }
        }

        $this->settings[$tab][$key][] = $item;
        $this->persistTab($tab);
    }

    public function removeRepeaterItem(string $tab, string $key, int $index): void
    {
        $this->pushHistory();
        unset($this->settings[$tab][$key][$index]);
        $this->settings[$tab][$key] = array_values($this->settings[$tab][$key]);
        $this->persistTab($tab);
    }

    /** Change the unit on all four sides of a sides-control at once. */
    public function setSidesUnit(string $tab, string $key, string $unit, ?string $device = null): void
    {
        $this->pushHistory();
        $target = &$this->settings[$tab][$key];
        if ($device !== null) {
            $target = &$target[$device];
        }

        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            if (isset($target[$side])) {
                $target[$side]['unit'] = $unit;
            }
        }

        $this->persistTab($tab);
    }

    private function persistTab(string $tab): void
    {
        $this->pushHistory("settings:{$this->selectedId}:{$tab}");
        $node = $this->node();
        if (! $node || ! isset($this->settings[$tab])) {
            return;
        }

        $data = $node->data ?? [];

        // Preserve internal keys (_col etc.) — they aren't schema fields, so
        // they're absent from the panel settings and must survive saves.
        $internal = array_filter(
            $data[$tab] ?? [],
            fn ($key) => str_starts_with((string) $key, '_'),
            ARRAY_FILTER_USE_KEY
        );

        $data[$tab] = $this->settings[$tab] + $internal;
        $node->update(['data' => $data]);
        $this->page->touch();
    }

    private function node(): ?PageNode
    {
        return $this->selectedId
            ? $this->draftNodes()->whereKey($this->selectedId)->first()
            : null;
    }

    /** All editor operations act on the DRAFT tree only. */
    private function draftNodes()
    {
        return $this->page->nodes()->where('is_draft', true);
    }

    private function draftRoots()
    {
        return $this->draftNodes()->whereNull('parent_id');
    }

    private function elementClass(PageNode $node): string
    {
        return app(ElementRegistry::class)->get($node->type);
    }

    private function loadSettings(): void
    {
        $this->settings = ['content' => [], 'style' => [], 'advanced' => []];
        $node = $this->node();
        if (! $node) {
            return;
        }

        $class = $this->elementClass($node);
        $tabs = [
            'content' => $class::contentFields(),
            'style' => $class::styleFields(),
            'advanced' => $class::advancedFields(),
        ];

        foreach ($tabs as $tab => $fields) {
            $stored = $node->settings($tab);
            foreach ($fields as $field) {
                $this->settings[$tab][$field->key] = $this->normalize($field, $stored[$field->key] ?? $field->default);
            }
        }
    }

    /** Coerce a stored value into the exact shape the panel inputs bind to. */
    private function normalize(Field $field, mixed $value): mixed
    {
        if ($field->responsive) {
            $isResponsive = is_array($value)
                && (isset($value['desktop']) || isset($value['tablet']) || isset($value['mobile']));

            $out = [];
            foreach (['desktop', 'tablet', 'mobile'] as $device) {
                $deviceValue = $isResponsive ? ($value[$device] ?? null) : ($device === 'desktop' ? $value : null);
                $out[$device] = $this->shape($field, $deviceValue);
            }

            return $out;
        }

        return $this->shape($field, $value);
    }

    private function shape(Field $field, mixed $value): mixed
    {
        $defaultUnit = $field->units[0] ?? 'px';

        if ($field->type === 'color' && $field->states) {
            return is_array($value)
                ? ['normal' => $value['normal'] ?? '', 'hover' => $value['hover'] ?? '']
                : ['normal' => is_scalar($value) ? (string) $value : '', 'hover' => ''];
        }

        return match ($field->type) {
            'unit' => is_array($value) && array_key_exists('value', $value)
                ? ['value' => $value['value'], 'unit' => $value['unit'] ?? $defaultUnit]
                : ['value' => is_scalar($value) ? $value : '', 'unit' => $defaultUnit],
            'sides' => (function () use ($value, $defaultUnit) {
                $out = [];
                foreach (['top', 'right', 'bottom', 'left'] as $side) {
                    $sideValue = is_array($value) ? ($value[$side] ?? null) : null;
                    $out[$side] = is_array($sideValue) && array_key_exists('value', $sideValue)
                        ? ['value' => $sideValue['value'], 'unit' => $sideValue['unit'] ?? $defaultUnit]
                        : ['value' => '', 'unit' => $defaultUnit];
                }

                return $out;
            })(),
            'link' => [
                'label' => $value['label'] ?? '',
                'url' => $value['url'] ?? '',
                'new_tab' => (bool) ($value['new_tab'] ?? false),
            ],
            'toggle' => (bool) $value,
            'columns' => is_array($value) && $value !== [] ? array_values($value) : [100],
            'repeater' => (function () use ($value, $field) {
                $items = is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
                foreach ($items as &$item) {
                    foreach ($field->fields as $sub) {
                        $item[$sub->key] ??= $sub->default;
                    }
                }

                return $items;
            })(),
            // scalar field types (text, select, color, …) must never hold
            // arrays — discard malformed/legacy-shaped stored values
            default => is_array($value) ? null : $value,
        };
    }

    public function render()
    {
        $rendered = app(PageRenderer::class)->renderEditor($this->page);

        $node = $this->node();
        $schema = $node ? $this->elementClass($node)::schema() : null;

        $registry = app(ElementRegistry::class);
        $library = collect($registry->schemas())->groupBy(fn ($s) => $s['group']);

        $walk = function ($nodes, int $depth, array $ancestors = []) use (&$walk, $registry): array {
            $rows = [];
            foreach ($nodes as $n) {
                $rows[] = [
                    'id' => $n->id,
                    'type' => $n->type,
                    'label' => $n->data['content']['_label'] ?? $registry->get($n->type)::label(),
                    'visible' => $n->visible,
                    'depth' => $depth,
                    'ancestors' => $ancestors,
                    'hasKids' => $n->children->isNotEmpty(),
                ];

                // list children in RENDER order: bucketed by column, then sort
                $children = $n->children->values();
                if ($n->type === 'container') {
                    $cols = max(1, count($n->data['content']['widths'] ?? [100]));
                    $children = $children
                        ->map(fn ($c, $i) => ['node' => $c, 'col' => min((int) ($c->data['content']['_col'] ?? ($i % $cols)), $cols - 1)])
                        ->sortBy(fn ($e) => [$e['col'], $e['node']->sort])
                        ->pluck('node')->values();
                }

                $rows = array_merge($rows, $walk($children, $depth + 1, [...$ancestors, $n->id]));
            }

            return $rows;
        };

        $tree = $walk(
            $this->draftRoots()->with('children.children.children')->orderBy('sort')->get(),
            0
        );

        return view('buildr::livewire.editor', [
            'rendered' => $rendered,
            'schema' => $schema,
            'fields' => $schema ? $schema['tabs'][$this->tab] : [],
            'library' => $library,
            'tree' => $tree,
            'isChild' => (bool) $node?->parent_id,
            'globalSwatches' => \Buildr\Render\GlobalCss::swatches(),
            'revisions' => $this->view === 'history'
                ? $this->page->revisions()->latest('id')->take(25)->get(['id', 'label', 'created_at'])
                : collect(),
            'dynTags' => [
                'Site Name' => '{{site.name}}',
                'Phone Number' => '{{site.phone}}',
                'Phone Link (tel:)' => '{{site.phone_link}}',
                'Email Address' => '{{site.email}}',
                'Street Address' => '{{site.address}}',
                'Current Year' => '{{year}}',
                "Today's Date" => '{{date:F j, Y}}',
                'Page Title' => '{{page.title}}',
                'Page Slug' => '{{page.slug}}',
                'Site URL' => '{{site.url}}',
                'Page URL' => '{{page.url}}',
            ],
            'undoCount' => count(cache()->get($this->historyKey('undo'), [])),
            'redoCount' => count(cache()->get($this->historyKey('redo'), [])),
            'recentMedia' => \Buildr\Models\Media::latest()->take(12)->get()
                ->map(fn ($m) => ['url' => $m->url(), 'name' => $m->name])->all(),
            'allMedia' => \Buildr\Models\Media::latest()->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => $m->url(),
                    'name' => $m->name,
                    'size' => $m->size >= 1048576 ? round($m->size / 1048576, 1).' MB' : max(1, (int) round($m->size / 1024)).' KB',
                    'date' => $m->created_at->format('M j, Y'),
                ])->all(),
        ])->title("Buildr — {$this->page->title}");
    }
}
