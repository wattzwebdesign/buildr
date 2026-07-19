<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

/** Native <details>/<summary> — zero JS, crawlable, exclusive via name attr. */
class Accordion extends Element
{
    public static string $icon = 'accordion';

    public static function view(): string
    {
        return 'buildr::elements.accordion';
    }

    public static function contentFields(): array
    {
        return [
            Field::repeater('items', [
                Field::text('title')->default('Accordion title'),
                Field::richtext('body')->default('Accordion content goes here.'),
            ])->default([
                ['title' => 'What areas do you serve?', 'body' => 'Answer goes here.'],
                ['title' => 'How do estimates work?', 'body' => 'Answer goes here.'],
            ]),
            Field::toggle('exclusive')->label('One open at a time')->default(true),
            Field::toggle('first_open')->label('First item open')->default(true),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('title_color')->section('Titles')->css('color')->target('summary'),
            ...Field::typographySet('title', 'summary', 'Titles'),
            ...Field::typographySet('body', '.acc-body', 'Content'),
            Field::color('background')->section('Items'),
            Field::sides('border_radius', ['px'])->section('Items'),
            Field::unit('item_gap', ['px', 'em'])->default(['value' => 8, 'unit' => 'px'])->section('Items'),
        ];
    }

    public function css(string $selector): array
    {
        $gap = $this->node->setting('style', 'item_gap');
        $g = is_array($gap) && ($gap['value'] ?? null) !== null && $gap['value'] !== '' ? $gap['value'].($gap['unit'] ?? 'px') : '8px';

        return [
            $selector => ['display' => 'flex', 'flex-direction' => 'column', 'gap' => $g],
            "{$selector} :where(details)" => array_filter([
                'border' => '1px solid #e5e7eb', 'border-radius' => '8px',
                'background' => $this->node->setting('style', 'background'),
            ]),
            "{$selector} :where(summary)" => array_filter([
                'cursor' => 'pointer', 'font-weight' => '600', 'padding' => '14px 16px',
                'color' => $this->node->setting('style', 'title_color'),
                'list-style' => 'none', 'display' => 'flex', 'align-items' => 'center', 'justify-content' => 'space-between',
            ]),
            "{$selector} :where(summary)::after" => ['content' => '"+"', 'font-weight' => '400', 'font-size' => '18px'],
            "{$selector} :where(details[open] summary)::after" => ['content' => '"\2212"'],
            "{$selector} :where(.acc-body)" => ['padding' => '0 16px 14px'],
        ];
    }
}
