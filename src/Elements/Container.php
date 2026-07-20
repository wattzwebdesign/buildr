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
            ])->label('Vertical align')->default('stretch')
                ->buttons(['stretch' => 'v-stretch', 'start' => 'v-top', 'center' => 'v-center', 'end' => 'v-bottom']),
            Field::select('col_halign', [
                '' => 'Default (stretch)',
                'flex-start' => 'Left',
                'center' => 'Center',
                'flex-end' => 'Right',
            ])->label('Align elements')->help('Horizontal alignment of stacked elements inside each column')
                ->buttons(['' => 'h-stretch', 'flex-start' => 'h-start', 'center' => 'h-center', 'flex-end' => 'h-end']),
            Field::select('col_valign', [
                '' => 'Default (top)',
                'center' => 'Center',
                'flex-end' => 'Bottom',
                'space-between' => 'Space between',
            ])->label('Distribute elements')->help('Vertical distribution of stacked elements inside each column')
                ->buttons(['' => 'v-top', 'center' => 'v-center', 'flex-end' => 'v-bottom', 'space-between' => 'v-between']),
            Field::unit('element_gap', ['px', 'em', 'rem'])->label('Element gap')
                ->help('Space between stacked elements in a column (default 12px)'),
            Field::unit('min_height', ['px', 'vh'])->responsive(),
            Field::select('width_mode', ['boxed' => 'Boxed', 'full' => 'Full width'])->default('boxed')
                ->buttons(['boxed' => 'boxed', 'full' => 'full']),
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
            Field::color('background')->section('Background'),
            Field::media('bg_image')->label('Background Image')->section('Background'),
            Field::select('bg_position', [
                '' => 'Default (center)', 'top left' => 'Top Left', 'top center' => 'Top Center', 'top right' => 'Top Right',
                'center left' => 'Center Left', 'center center' => 'Center Center', 'center right' => 'Center Right',
                'bottom left' => 'Bottom Left', 'bottom center' => 'Bottom Center', 'bottom right' => 'Bottom Right',
            ])->label('Position')->section('Background'),
            Field::select('bg_attachment', ['' => 'Default', 'scroll' => 'Scroll', 'fixed' => 'Fixed'])
                ->label('Attachment')->section('Background'),
            Field::select('bg_repeat', ['' => 'Default (no repeat)', 'no-repeat' => 'No Repeat', 'repeat' => 'Repeat', 'repeat-x' => 'Repeat X', 'repeat-y' => 'Repeat Y'])
                ->label('Repeat')->section('Background'),
            Field::select('bg_size', ['' => 'Default (cover)', 'cover' => 'Cover', 'contain' => 'Contain', 'auto' => 'Auto'])
                ->label('Display Size')->section('Background'),
            Field::select('border_style', ['none' => 'None', 'solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted'])->default('none')
                ->buttons(['none' => 'ban', 'solid' => 'line-solid', 'dashed' => 'line-dashed', 'dotted' => 'line-dotted'])->section('Border'),
            Field::sides('border_width', ['px'])->section('Border'),
            Field::color('border_color')->section('Border'),
            Field::sides('border_radius', ['px', '%'])->section('Border'),
            Field::select('shadow', ['' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'])->section('Effects'),
        ];
    }

    public function css(string $selector): array
    {
        $content = $this->node->settings('content');
        $widths = $content['widths'] ?? [100];

        // Boxed max-width/centering applies to page-level sections only —
        // on a nested container (a grid item) margin-inline:auto would eat
        // the free space and shrink the column to its content.
        $boxed = ($content['width_mode'] ?? 'boxed') === 'boxed' && $this->node->parent_id === null;

        $rules = [
            $selector => array_filter([
                'display' => 'grid',
                'grid-template-columns' => implode(' ', array_map(fn ($w) => $w.'fr', $widths)),
                'align-items' => ($content['valign'] ?? 'stretch') !== 'stretch' ? $content['valign'] : null,
                'max-width' => $boxed
                    ? $this->unitValue($content['max_width'] ?? ['value' => 1160, 'unit' => 'px'])
                    : null,
                'margin-inline' => $boxed ? 'auto' : null,
            ]),
        ];

        if (($content['stack_mobile'] ?? true) && count($widths) > 1) {
            $rules['@mobile'][$selector] = ['grid-template-columns' => '1fr'];
        }

        // Background image + positioning
        $style = $this->node->settings('style');
        if (! empty($style['bg_image'])) {
            $rules[$selector]['background-image'] = "url('".$style['bg_image']."')";
            $rules[$selector]['background-position'] = $style['bg_position'] ?? '' ?: 'center';
            $rules[$selector]['background-repeat'] = $style['bg_repeat'] ?? '' ?: 'no-repeat';
            $rules[$selector]['background-size'] = $style['bg_size'] ?? '' ?: 'cover';
            if (! empty($style['bg_attachment'])) {
                $rules[$selector]['background-attachment'] = $style['bg_attachment'];
            }
        }

        // Flex controls for the column stacks (.bcol wrappers)
        $stack = array_filter([
            'align-items' => $content['col_halign'] ?? null,
            'justify-content' => $content['col_valign'] ?? null,
            'gap' => isset($content['element_gap']['value']) && $content['element_gap']['value'] !== '' && $content['element_gap']['value'] !== null
                ? $this->unitValue($content['element_gap'])
                : null,
        ]);
        if ($stack !== []) {
            $rules["{$selector} > .bcol"] = $stack;
        }

        return $rules;
    }

    private function unitValue(array $v): string
    {
        return ($v['value'] ?? 0).($v['unit'] ?? 'px');
    }
}
