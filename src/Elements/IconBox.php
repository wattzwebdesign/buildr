<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;
use Buildr\Support\Icons;

class IconBox extends Element
{
    public static string $icon = 'icon-box';

    public static function view(): string
    {
        return 'buildr::elements.icon_box';
    }

    public static function contentFields(): array
    {
        return [
            Field::select('icon', Icons::options())->default('star'),
            Field::text('heading')->default('Icon Box Title'),
            Field::richtext('body')->default('Describe the service or feature here in a sentence or two.'),
            Field::link('link'),
            Field::select('layout', ['top' => 'Icon top', 'left' => 'Icon left'])->default('top')
                ->buttons(['top' => 'v-top', 'left' => 'h-start']),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::unit('icon_size', ['px', 'em'])->default(['value' => 32, 'unit' => 'px'])->section('Icon'),
            Field::color('icon_color')->section('Icon')->css('color')->target('.ib-icon svg'),
            Field::color('heading_color')->section('Heading')->css('color')->target('h3'),
            ...Field::typographySet('heading', 'h3', 'Heading'),
            Field::color('text_color')->section('Description')->css('color')->target('.ib-body'),
            ...Field::typographySet('text', '.ib-body', 'Description'),
            Field::color('background')->section('Box'),
            Field::sides('border_radius', ['px', '%'])->section('Box'),
            Field::sides('box_padding', ['px', 'em'])->label('Padding')->section('Box')->css('padding'),
        ];
    }

    public function css(string $selector): array
    {
        $s = fn ($k, $d) => $this->node->setting('style', $k) ?: $d;
        $size = $this->node->setting('style', 'icon_size');
        $px = is_array($size) && ($size['value'] ?? null) !== null && $size['value'] !== ''
            ? $size['value'].($size['unit'] ?? 'px') : '32px';
        $left = $this->node->setting('content', 'layout') === 'left';

        return array_filter([
            $selector => array_filter([
                'display' => 'flex',
                'flex-direction' => $left ? 'row' : 'column',
                'gap' => '12px',
                'align-items' => $left ? 'flex-start' : null,
            ]),
            "{$selector} :where(.ib-icon svg)" => ['width' => $px, 'height' => $px, 'color' => $s('icon_color', '#1f2933') ?: '#1f2933'],
        ], fn ($v) => $v !== []);
    }
}
