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
        $data[$tab] = $this->settings[$tab];
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
            default => $value,
        };
    }

    public function render()
    {
        $rendered = app(PageRenderer::class)->renderEditor($this->page);

        $node = $this->node();
        $schema = $node ? $this->elementClass($node)::schema() : null;

        return view('buildr::livewire.editor', [
            'rendered' => $rendered,
            'schema' => $schema,
            'fields' => $schema ? $schema['tabs'][$this->tab] : [],
        ])->title("Buildr — {$this->page->title}");
    }
}
