<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;
use Buildr\Support\Icons;

class IconList extends Element
{
    public static string $icon = 'list';

    public static function view(): string
    {
        return 'buildr::elements.icon_list';
    }

    public static function contentFields(): array
    {
        return [
            Field::repeater('items', [
                Field::select('icon', Icons::options())->default('check'),
                Field::text('text')->default('List item'),
                Field::text('url')->label('Link'),
            ])->default([
                ['icon' => 'check', 'text' => 'List item one', 'url' => ''],
                ['icon' => 'check', 'text' => 'List item two', 'url' => ''],
                ['icon' => 'check', 'text' => 'List item three', 'url' => ''],
            ]),
            Field::select('layout', ['vertical' => 'Vertical', 'inline' => 'Inline'])->default('vertical')
                ->buttons(['vertical' => 'v-top', 'inline' => 'h-stretch']),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::unit('icon_size', ['px', 'em'])->default(['value' => 18, 'unit' => 'px'])->section('Icons'),
            Field::color('icon_color')->section('Icons'),
            Field::color('text_color')->section('Text'),
            Field::unit('item_gap', ['px', 'em'])->default(['value' => 10, 'unit' => 'px'])->section('Spacing'),
        ];
    }

    public function css(string $selector): array
    {
        $gap = $this->node->setting('style', 'item_gap');
        $size = $this->node->setting('style', 'icon_size');
        $u = fn ($v, $d) => is_array($v) && ($v['value'] ?? null) !== null && $v['value'] !== '' ? $v['value'].($v['unit'] ?? 'px') : $d;

        return [
            $selector => array_filter([
                'display' => 'flex',
                'flex-direction' => $this->node->setting('content', 'layout') === 'inline' ? 'row' : 'column',
                'flex-wrap' => 'wrap',
                'gap' => $u($gap, '10px'),
                'list-style' => 'none',
                'padding' => '0',
                'color' => $this->node->setting('style', 'text_color'),
            ]),
            "{$selector} :where(li)" => ['display' => 'flex', 'align-items' => 'center', 'gap' => '8px'],
            "{$selector} :where(svg)" => array_filter([
                'width' => $u($size, '18px'), 'height' => $u($size, '18px'), 'flex' => 'none',
                'color' => $this->node->setting('style', 'icon_color'),
            ]),
        ];
    }
}
