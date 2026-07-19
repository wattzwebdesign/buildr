<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

/** Pure-CSS tabs (hidden radios) — no JS on the public page, crawlable panels. */
class Tabs extends Element
{
    public static string $icon = 'tabs';

    public static function view(): string
    {
        return 'buildr::elements.tabs';
    }

    public static function contentFields(): array
    {
        return [
            Field::repeater('tabs', [
                Field::text('label')->default('Tab'),
                Field::richtext('body')->default('Tab content.'),
            ])->default([
                ['label' => 'Tab one', 'body' => 'First tab content.'],
                ['label' => 'Tab two', 'body' => 'Second tab content.'],
            ]),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('active_color')->section('Labels'),
            Field::color('label_color')->section('Labels'),
            ...Field::typographySet('label', 'label', 'Labels'),
        ];
    }

    public function css(string $selector): array
    {
        $accent = $this->node->setting('style', 'active_color') ?: '#1f2933';
        $count = count($this->node->setting('content', 'tabs') ?? []);

        $rules = [
            "{$selector} :where(.tb-radio)" => ['position' => 'absolute', 'opacity' => '0', 'pointer-events' => 'none'],
            "{$selector} :where(.tb-labels)" => ['display' => 'flex', 'gap' => '4px', 'border-bottom' => '2px solid #e5e7eb', 'margin-bottom' => '16px'],
            "{$selector} :where(label)" => array_filter([
                'padding' => '10px 16px', 'cursor' => 'pointer', 'font-weight' => '600',
                'border-bottom' => '2px solid transparent', 'margin-bottom' => '-2px',
                'color' => $this->node->setting('style', 'label_color') ?: '#6b7280',
            ]),
            "{$selector} :where(.tb-panel)" => ['display' => 'none'],
        ];

        for ($i = 1; $i <= $count; $i++) {
            $rules["{$selector} .tb-radio:nth-of-type({$i}):checked ~ .tb-panels .tb-panel:nth-of-type({$i})"] = ['display' => 'block'];
            $rules["{$selector} .tb-radio:nth-of-type({$i}):checked ~ .tb-labels label:nth-of-type({$i})"] = [
                'color' => $accent, 'border-bottom-color' => $accent,
            ];
        }

        return $rules;
    }
}
