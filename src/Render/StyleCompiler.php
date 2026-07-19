<?php

namespace Buildr\Render;

use Buildr\Elements\Element;
use Buildr\Models\PageNode;

/**
 * Turns node settings into one compiled stylesheet per page. Values follow
 * the shapes documented on Field: units are {value, unit}, responsive values
 * are keyed by device, sides are keyed top/right/bottom/left.
 *
 * Emits desktop rules first, then @media blocks for tablet/mobile using the
 * configured breakpoints.
 */
class StyleCompiler
{
    /** Style keys mapped 1:1 to CSS properties. */
    private const PROPS = [
        'color' => 'color',
        'font_family' => 'font-family',
        'background' => 'background',
        'font_size' => 'font-size',
        'font_weight' => 'font-weight',
        'line_height' => 'line-height',
        'letter_spacing' => 'letter-spacing',
        'text_transform' => 'text-transform',
        'text_align' => 'text-align',
        'width' => 'width',
        'max_width' => 'max-width',
        'height' => 'height',
        'min_height' => 'min-height',
        'gap' => 'gap',
        'object_fit' => 'object-fit',
        'border_style' => 'border-style',
        'border_color' => 'border-color',
    ];

    private const SIDE_PROPS = [
        'margin' => 'margin',
        'padding' => 'padding',
        'border_width' => 'border-width',
        'border_radius' => 'border-radius',
    ];

    /** @var array{desktop: array, tablet: array, mobile: array} */
    private array $rules = ['desktop' => [], 'tablet' => [], 'mobile' => []];

    private array $custom = [];

    public function __construct(private array $breakpoints)
    {
    }

    public function addNode(PageNode $node, Element $element): void
    {
        $selector = '.'.$node->cssId();
        $settings = $node->settings('style') + $node->settings('content');

        foreach (self::PROPS as $key => $prop) {
            $this->addValue($selector, $prop, $settings[$key] ?? null);
        }

        foreach (self::SIDE_PROPS as $key => $prop) {
            $this->addSides($selector, $prop, $settings[$key] ?? null);
        }

        $advanced = $node->settings('advanced');
        foreach (['margin', 'padding'] as $key) {
            $this->addSides($selector, $key, $advanced[$key] ?? null);
        }

        // Align-self covers both axes' semantics: cross-axis in a flex
        // column stack, inline-axis (justify-self) as a bare grid item.
        if (! empty($advanced['align'])) {
            $this->addValue($selector, 'align-self', $advanced['align']);
            $this->addValue($selector, 'justify-self', $advanced['align']);
        }

        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            if ($advanced["hide_{$device}"] ?? false) {
                $this->rules[$device][$selector]['display'] = 'none';
            }
        }

        // Element-specific declarations (containers, dividers, …).
        foreach ($element->css($selector) as $sel => $declarations) {
            if (str_starts_with($sel, '@')) {
                $device = ltrim($sel, '@');
                foreach ($declarations as $innerSel => $inner) {
                    $this->rules[$device][$innerSel] = ($this->rules[$device][$innerSel] ?? []) + $inner;
                }
            } else {
                $this->rules['desktop'][$sel] = ($this->rules['desktop'][$sel] ?? []) + $declarations;
            }
        }

        if ($css = $advanced['custom_css'] ?? null) {
            $this->custom[] = str_contains($css, '{') ? $css : "{$selector}{{$css}}";
        }
    }

    public function compile(): string
    {
        $out = $this->block($this->rules['desktop']);

        foreach (['tablet', 'mobile'] as $device) {
            if ($this->rules[$device] !== []) {
                $out .= "@media(max-width:{$this->breakpoints[$device]}px){".$this->block($this->rules[$device]).'}';
            }
        }

        return $out.implode('', $this->custom);
    }

    private function block(array $rules): string
    {
        $css = '';

        foreach ($rules as $selector => $declarations) {
            if ($declarations === []) {
                continue;
            }
            $body = '';
            foreach ($declarations as $prop => $value) {
                $body .= "{$prop}:{$value};";
            }
            $css .= "{$selector}{{$body}}";
        }

        return $css;
    }

    private function addValue(string $selector, string $prop, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        foreach ($this->perDevice($value) as $device => $deviceValue) {
            $css = $this->toCss($deviceValue);
            if ($css !== null) {
                $this->rules[$device][$selector][$prop] = $css;
            }
        }
    }

    private function addSides(string $selector, string $prop, mixed $value): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($this->perDevice($value) as $device => $sides) {
            if (! is_array($sides)) {
                continue;
            }
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $css = $this->toCss($sides[$side] ?? null);
                if ($css !== null) {
                    $sideProp = match ($prop) {
                        'border-radius' => 'border-'.match ($side) {
                            'top' => 'top-left', 'right' => 'top-right',
                            'bottom' => 'bottom-right', 'left' => 'bottom-left',
                        }.'-radius',
                        'border-width' => "border-{$side}-width",
                        default => "{$prop}-{$side}",
                    };
                    $this->rules[$device][$selector][$sideProp] = $css;
                }
            }
        }
    }

    /** Split a possibly-responsive value into device => value. */
    private function perDevice(mixed $value): array
    {
        if (is_array($value) && (isset($value['desktop']) || isset($value['tablet']) || isset($value['mobile']))) {
            return array_intersect_key($value, ['desktop' => 1, 'tablet' => 1, 'mobile' => 1]);
        }

        return ['desktop' => $value];
    }

    /** A scalar passes through; a {value, unit} pair concatenates. */
    private function toCss(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_array($value)) {
            if (! isset($value['value']) || $value['value'] === '' || $value['value'] === null) {
                return null;
            }

            return $value['value'].($value['unit'] ?? '');
        }

        return (string) $value;
    }
}
