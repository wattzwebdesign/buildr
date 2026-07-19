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

        $first = $page->rootNodes()->first();
        if ($first) {
            $this->selectNode($first->id);
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
        $this->page->update(['published_at' => now()]);
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
        $node = $this->page->nodes()->whereKey($containerId)->first();
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
        $container = $this->page->nodes()->whereKey($containerId)->first();
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
        $this->dropInto($type, $containerId, 0, $cols);
    }

    /** Move an existing node before/after another node (drag reorder). */
    public function moveNodeRelative(int $nodeId, int $targetId, string $position): void
    {
        $node = $this->page->nodes()->whereKey($nodeId)->first();
        $target = $this->page->nodes()->whereKey($targetId)->first();

        if (! $node || ! $target || $node->id === $target->id || ! $target->parent_id) {
            return;
        }
        if ($this->containsNode($node, $target->parent_id)) {
            return; // can't move a container inside itself
        }

        $oldParent = $node->parent_id;
        $sort = $target->sort + ($position === 'after' ? 1 : 0);

        $this->page->nodes()->where('parent_id', $target->parent_id)->where('sort', '>=', $sort)->increment('sort');

        $data = $node->data ?? [];
        $data['content']['_col'] = $target->data['content']['_col'] ?? 0;
        $node->update(['parent_id' => $target->parent_id, 'sort' => $sort, 'data' => $data]);

        $this->resequence($oldParent);
        $this->resequence($target->parent_id);
        $this->page->touch();
        $this->selectNode($node->id);
    }

    /** Move an existing node into a container column (append). */
    public function moveNodeToColumn(int $nodeId, int $containerId, int $col): void
    {
        $node = $this->page->nodes()->whereKey($nodeId)->first();
        $container = $this->page->nodes()->whereKey($containerId)->first();

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
        $current = $this->page->nodes()->whereKey($possibleDescendantId)->first();
        while ($current) {
            if ($current->id === $node->id) {
                return true;
            }
            $current = $current->parent_id ? $this->page->nodes()->whereKey($current->parent_id)->first() : null;
        }

        return false;
    }

    public function toggleNav(): void
    {
        $this->showNav = ! $this->showNav;
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
            $this->page->nodes()->where('parent_id', $current->parent_id)->where('sort', '>=', $sort)->increment('sort');
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

        $this->page->touch();
        $this->selectNode($node->id);
    }

    public function addElement(string $type, ?int $col = null): void
    {
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
                'sort' => ($this->page->rootNodes()->max('sort') ?? -1) + 1,
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
        $node = $this->page->nodes()->whereKey($id)->first();
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
        $node = $this->page->nodes()->whereKey($id)->first();
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
        $node = $this->page->nodes()->whereKey($id)->first();
        if (! $node) {
            return;
        }

        $parentId = $node->parent_id;
        $node->children()->get()->each(fn ($c) => $c->delete());
        $node->delete();
        $this->resequence($parentId);
        $this->page->touch();

        if ($this->selectedId === $id || ! $this->node()) {
            $fallback = $parentId ?: $this->page->rootNodes()->first()?->id;
            $fallback ? $this->selectNode($fallback) : $this->clearSelection();
        }
    }

    public function toggleVisible(int $id): void
    {
        $node = $this->page->nodes()->whereKey($id)->first();
        $node?->update(['visible' => ! $node->visible]);
        $this->page->touch();
    }

    private function clearSelection(): void
    {
        $this->selectedId = null;
        $this->settings = ['content' => [], 'style' => [], 'advanced' => []];
    }

    private function insertSort(): int
    {
        if ($this->insertAfter === null) {
            return ($this->page->rootNodes()->max('sort') ?? -1) + 1;
        }

        if ($this->insertAfter === 0) {
            $this->page->rootNodes()->increment('sort');

            return 0;
        }

        $after = $this->page->rootNodes()->whereKey($this->insertAfter)->first();
        $sort = ($after?->sort ?? -1) + 1;
        $this->page->rootNodes()->where('sort', '>=', $sort)->increment('sort');

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
        $this->page->nodes()
            ->where('parent_id', $parentId)
            ->orderBy('sort')->get()
            ->each(fn ($n, $i) => $n->sort === $i || $n->update(['sort' => $i]));
    }

    /** Any panel edit: persist the edited tab's settings into the node. */
    public function updatedSettings(mixed $value, string $key): void
    {
        $this->persistTab(explode('.', $key, 2)[0]);
    }

    /** Container structure preset, e.g. "50,50". */
    public function setWidths(string $preset): void
    {
        $widths = array_values(array_filter(array_map('intval', explode(',', $preset))));
        if ($widths === []) {
            return;
        }

        $this->settings['content']['widths'] = $widths;
        $this->persistTab('content');
    }

    /** Change the unit on all four sides of a sides-control at once. */
    public function setSidesUnit(string $tab, string $key, string $unit, ?string $device = null): void
    {
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
            ? $this->page->nodes()->whereKey($this->selectedId)->first()
            : null;
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

        $walk = function ($nodes, int $depth) use (&$walk, $registry): array {
            $rows = [];
            foreach ($nodes as $n) {
                $rows[] = [
                    'id' => $n->id,
                    'label' => $registry->get($n->type)::label(),
                    'visible' => $n->visible,
                    'depth' => $depth,
                ];
                $rows = array_merge($rows, $walk($n->children, $depth + 1));
            }

            return $rows;
        };

        $tree = $walk(
            $this->page->nodes()->whereNull('parent_id')->with('children.children.children')->orderBy('sort')->get(),
            0
        );

        return view('buildr::livewire.editor', [
            'rendered' => $rendered,
            'schema' => $schema,
            'fields' => $schema ? $schema['tabs'][$this->tab] : [],
            'library' => $library,
            'tree' => $tree,
            'isChild' => (bool) $node?->parent_id,
        ])->title("Buildr — {$this->page->title}");
    }
}
