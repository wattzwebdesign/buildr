<?php

namespace Buildr\Fields;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * One fluent class describes every control in the matrix. A Field is pure
 * schema: it serializes to an array the editor renders, and documents the
 * shape of the value stored in the node's data JSON.
 *
 * Stored value shapes:
 *   unit        {"value": 16, "unit": "px"}  (responsive: keyed by device)
 *   color       "#14324f" | "var(--g-primary)"
 *   link        {"label": "...", "url": "...", "new_tab": false}
 *   sides       {"top": {...unit}, "right": {...}, "bottom": {...}, "left": {...}}
 *   repeater    [ {field values...}, ... ]
 *   responsive  {"desktop": <value>, "tablet": <value>, "mobile": <value>}
 */
class Field implements Arrayable
{
    public string $label;
    public mixed $default = null;
    public array $options = [];      // select choices: value => label
    public array $units = [];        // unit fields: allowed units
    public array $fields = [];       // repeater sub-fields
    public bool $responsive = false;
    public bool $states = false;     // normal / hover
    public bool $required = false;
    public ?string $help = null;
    public ?string $section = null;  // collapsible panel group name
    public ?string $cssProp = null;  // compile straight to this CSS property
    public ?string $target = null;   // scoped sub-selector inside the element
    public bool $buttons = false;    // render select as icon button group
    public array $icons = [];        // option value => icon name (for buttons mode)

    private function __construct(
        public string $type,
        public string $key,
    ) {
        $this->label = Str::headline($key);
    }

    public static function make(string $type, string $key): static
    {
        return new static($type, $key);
    }

    public static function text(string $key): static { return static::make('text', $key); }
    public static function textarea(string $key): static { return static::make('textarea', $key); }
    public static function richtext(string $key): static { return static::make('richtext', $key); }
    public static function number(string $key): static { return static::make('number', $key); }
    public static function toggle(string $key): static { return static::make('toggle', $key); }
    public static function color(string $key): static { return static::make('color', $key); }
    public static function link(string $key): static { return static::make('link', $key); }
    public static function media(string $key): static { return static::make('media', $key); }
    public static function icon(string $key): static { return static::make('icon', $key); }
    public static function code(string $key): static { return static::make('code', $key); }

    public static function select(string $key, array $options): static
    {
        $field = static::make('select', $key);
        $field->options = $options;

        return $field;
    }

    public static function unit(string $key, array $units = ['px', '%', 'em', 'rem', 'vw', 'vh']): static
    {
        $field = static::make('unit', $key);
        $field->units = $units;

        return $field;
    }

    /** Four-sided unit value (margin, padding, border width/radius). */
    public static function sides(string $key, array $units = ['px', '%', 'em', 'rem']): static
    {
        $field = static::make('sides', $key);
        $field->units = $units;

        return $field;
    }

    /** @param  array<int, Field>  $fields */
    public static function repeater(string $key, array $fields): static
    {
        $field = static::make('repeater', $key);
        $field->fields = $fields;

        return $field;
    }

    public function label(string $label): static { $this->label = $label; return $this; }
    public function default(mixed $default): static { $this->default = $default; return $this; }
    public function help(string $help): static { $this->help = $help; return $this; }
    public function required(): static { $this->required = true; return $this; }
    public function responsive(): static { $this->responsive = true; return $this; }
    public function section(string $name): static { $this->section = $name; return $this; }
    public function css(string $prop): static { $this->cssProp = $prop; return $this; }
    public function target(string $selector): static { $this->target = $selector; return $this; }

    /**
     * Full typography control set compiling into a scoped sub-selector —
     * gives composite widgets per-part typography (heading vs body etc.).
     *
     * @return array<int, Field>
     */
    public static function typographySet(string $prefix, ?string $target, string $section): array
    {
        $apply = function (Field $field) use ($target, $section): Field {
            $field->section($section);

            return $target ? $field->target($target) : $field;
        };

        return array_map($apply, [
            static::select("{$prefix}_font_family", \Buildr\Support\Fonts::options('Default'))
                ->label('Font Family')->css('font-family'),
            static::unit("{$prefix}_font_size", ['px', 'em', 'rem'])->label('Font Size')->responsive()->css('font-size'),
            static::select("{$prefix}_font_weight", array_combine(range(100, 900, 100), range(100, 900, 100)))
                ->label('Font Weight')->css('font-weight'),
            static::unit("{$prefix}_line_height", ['', 'px', 'em'])->label('Line Height')->css('line-height'),
            static::unit("{$prefix}_letter_spacing", ['px', 'em'])->label('Letter Spacing')->css('letter-spacing'),
            static::select("{$prefix}_text_transform", ['none' => 'None', 'uppercase' => 'Uppercase', 'lowercase' => 'Lowercase', 'capitalize' => 'Capitalize'])
                ->label('Text Transform')->css('text-transform'),
        ]);
    }
    public function states(): static { $this->states = true; return $this; }

    /** Render this select as a one-click icon group instead of a dropdown. */
    public function buttons(array $icons = []): static
    {
        $this->buttons = true;
        $this->icons = $icons;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'key' => $this->key,
            'label' => $this->label,
            'default' => $this->default,
            'options' => $this->options,
            'units' => $this->units,
            'fields' => array_map(fn (Field $f) => $f->toArray(), $this->fields),
            'responsive' => $this->responsive,
            'states' => $this->states,
            'required' => $this->required,
            'help' => $this->help,
            'section' => $this->section,
            'buttons' => $this->buttons,
            'icons' => $this->icons,
        ], fn ($v) => $v !== null && $v !== [] && $v !== false);
    }
}
