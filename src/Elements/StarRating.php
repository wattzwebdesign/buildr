<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class StarRating extends Element
{
    public static string $icon = 'star';

    public static function view(): string
    {
        return 'buildr::elements.star_rating';
    }

    public static function contentFields(): array
    {
        return [
            Field::number('rating')->default(5)->help('0–5, halves allowed'),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::unit('size', ['px', 'em'])->default(['value' => 20, 'unit' => 'px']),
            Field::color('color'),
            Field::color('unfilled_color'),
        ];
    }

    public function css(string $selector): array
    {
        $size = $this->node->setting('style', 'size');
        $px = is_array($size) && ($size['value'] ?? null) !== null && $size['value'] !== ''
            ? $size['value'].($size['unit'] ?? 'px') : '20px';

        return [
            $selector => ['display' => 'inline-flex', 'gap' => '3px', 'color' => $this->node->setting('style', 'color') ?: '#f59e0b'],
            "{$selector} :where(svg)" => ['width' => $px, 'height' => $px],
            "{$selector} :where(.st-e)" => ['color' => $this->node->setting('style', 'unfilled_color') ?: '#d1d5db'],
        ];
    }
}
