<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

/**
 * Renders as a single element with CSS grid — columns are grid tracks,
 * never wrapper divs. Children carry a `col` index in their data to place
 * themselves in a track.
 */
class Container extends Element
{
    public static ?string $key = 'container';
    public static string $icon = 'layout';
    public static string $group = 'layout';

    public static function view(): string
    {
        return 'buildr::elements.container';
    }

    public static function contentFields(): array
    {
        return [
            Field::make('columns', 'widths')->label('Column widths')
                ->default([100])
                ->help('Percentages per column, e.g. [50, 50] or [33, 67]'),
            Field::unit('gap', ['px', '%', 'em'])->responsive()
                ->default(['value' => 24, 'unit' => 'px']),
            Field::select('valign', [
                'stretch' => 'Stretch',
                'start' => 'Top',
                'center' => 'Center',
                'end' => 'Bottom',
            ])->label('Vertical align')->default('stretch'),
            Field::unit('min_height', ['px', 'vh'])->responsive(),
            Field::select('width_mode', ['boxed' => 'Boxed', 'full' => 'Full width'])->default('boxed'),
            Field::unit('max_width', ['px', '%'])->default(['value' => 1160, 'unit' => 'px']),
            Field::select('tag', [
                'div' => 'div', 'section' => 'section', 'header' => 'header',
                'footer' => 'footer', 'main' => 'main', 'aside' => 'aside', 'article' => 'article',
            ])->label('HTML tag')->default('div'),
            Field::toggle('stack_mobile')->label('Stack columns on mobile')->default(true),
            Field::toggle('reverse_mobile')->label('Reverse order when stacked')->default(false),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('background'),
            Field::select('border_style', ['none' => 'None', 'solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted'])->default('none'),
            Field::sides('border_width', ['px']),
            Field::color('border_color'),
            Field::sides('border_radius', ['px', '%']),
        ];
    }

    public function css(string $selector): array
    {
        $content = $this->node->settings('content');
        $widths = $content['widths'] ?? [100];

        $rules = [
            $selector => array_filter([
                'display' => 'grid',
                'grid-template-columns' => implode(' ', array_map(fn ($w) => $w.'fr', $widths)),
                'align-items' => ($content['valign'] ?? 'stretch') !== 'stretch' ? $content['valign'] : null,
                'max-width' => ($content['width_mode'] ?? 'boxed') === 'boxed'
                    ? $this->unitValue($content['max_width'] ?? ['value' => 1160, 'unit' => 'px'])
                    : null,
                'margin-inline' => ($content['width_mode'] ?? 'boxed') === 'boxed' ? 'auto' : null,
            ]),
        ];

        if (($content['stack_mobile'] ?? true) && count($widths) > 1) {
            $rules['@mobile'][$selector] = ['grid-template-columns' => '1fr'];
        }

        return $rules;
    }

    private function unitValue(array $v): string
    {
        return ($v['value'] ?? 0).($v['unit'] ?? 'px');
    }
}
