<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;
use Buildr\Models\PageNode;
use Buildr\Render\PageRenderer;
use Illuminate\Support\Str;

/**
 * Base for everything placeable on a page: core elements, containers, and
 * per-site coded blocks. Subclasses declare a schema (fields per tab) and a
 * Blade view; rendering produces bare semantic markup — the lean-DOM
 * contract lives here.
 */
abstract class Element
{
    /** Unique registry key, e.g. "heading". Defaults from the class name. */
    public static ?string $key = null;

    public static ?string $label = null;

    /** Lucide icon name shown in the editor library. */
    public static string $icon = 'square';

    /** Library group: layout | elements | sections. */
    public static string $group = 'elements';

    abstract public static function view(): string;

    /** @return array<int, Field> */
    abstract public static function contentFields(): array;

    /** @return array<int, Field> */
    public static function styleFields(): array
    {
        return [];
    }

    /** Advanced tab — identical across every element (ADV-1..7). */
    public static function advancedFields(): array
    {
        return [
            Field::select('align', [
                '' => 'Default',
                'start' => 'Start',
                'center' => 'Center',
                'end' => 'End',
                'stretch' => 'Stretch',
            ])->label('Align self')->responsive()
                ->buttons(['' => 'ban', 'start' => 'h-start', 'center' => 'h-center', 'end' => 'h-end', 'stretch' => 'h-stretch']),
            Field::sides('margin')->responsive()->section('Spacing'),
            Field::sides('padding')->responsive()->section('Spacing'),
            Field::text('anchor_id')->label('Anchor ID')->section('Attributes'),
            Field::text('css_class')->label('CSS classes')->section('Attributes'),
            Field::toggle('hide_desktop')->section('Visibility'),
            Field::toggle('hide_tablet')->section('Visibility'),
            Field::toggle('hide_mobile')->section('Visibility'),
            Field::code('custom_css')->label('Custom CSS')->section('Custom CSS'),
        ];
    }

    public static function key(): string
    {
        return static::$key ?? Str::snake(class_basename(static::class));
    }

    public static function label(): string
    {
        return static::$label ?? Str::headline(class_basename(static::class));
    }

    /** Full schema, serialized for the editor. */
    public static function schema(): array
    {
        $serialize = fn (array $fields) => array_map(fn (Field $f) => $f->toArray(), $fields);

        return [
            'key' => static::key(),
            'label' => static::label(),
            'icon' => static::$icon,
            'group' => static::$group,
            'tabs' => [
                'content' => $serialize(static::contentFields()),
                'style' => $serialize(static::styleFields()),
                'advanced' => $serialize(static::advancedFields()),
            ],
        ];
    }

    public function __construct(public PageNode $node)
    {
    }

    /** View data: content settings (defaults applied) + the node itself. */
    public function viewData(PageRenderer $renderer): array
    {
        $content = $this->node->settings('content');

        foreach (static::contentFields() as $field) {
            $content[$field->key] ??= $field->default;
            $content[$field->key] = $this->resolveTags($content[$field->key], $renderer);
        }

        return $content + ['node' => $this->node, 'renderer' => $renderer];
    }

    /** Resolve {{tags}} in a value — strings directly, arrays recursively. */
    private function resolveTags(mixed $value, PageRenderer $renderer): mixed
    {
        if (is_string($value)) {
            return $renderer->tags()->resolve($value, ['page' => $this->node->page]);
        }
        if (is_array($value)) {
            return array_map(fn ($v) => $this->resolveTags($v, $renderer), $value);
        }

        return $value;
    }

    public function render(PageRenderer $renderer): string
    {
        return view(static::view(), $this->viewData($renderer))->render();
    }

    /**
     * Extra CSS declarations beyond what StyleCompiler derives generically.
     * $selector is ".b{id}". Return [selector => [prop => value]].
     */
    public function css(string $selector): array
    {
        return [];
    }
}
