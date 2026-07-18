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
            Field::sides('margin')->responsive(),
            Field::sides('padding')->responsive(),
            Field::text('anchor_id')->label('Anchor ID'),
            Field::text('css_class')->label('CSS classes'),
            Field::toggle('hide_desktop'),
            Field::toggle('hide_tablet'),
            Field::toggle('hide_mobile'),
            Field::code('custom_css')->label('Custom CSS'),
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

            if (is_string($content[$field->key])) {
                $content[$field->key] = $renderer->tags()->resolve($content[$field->key], [
                    'page' => $this->node->page,
                ]);
            }
        }

        return $content + ['node' => $this->node, 'renderer' => $renderer];
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
