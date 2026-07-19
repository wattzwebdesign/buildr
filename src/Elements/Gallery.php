<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Gallery extends Element
{
    public static string $icon = 'gallery';

    public static function view(): string
    {
        return 'buildr::elements.gallery';
    }

    public static function contentFields(): array
    {
        return [
            Field::repeater('images', [
                Field::media('src')->default('/buildr-assets/placeholder.svg'),
                Field::text('alt'),
            ])->default([
                ['src' => '/buildr-assets/placeholder.svg', 'alt' => ''],
                ['src' => '/buildr-assets/placeholder.svg', 'alt' => ''],
                ['src' => '/buildr-assets/placeholder.svg', 'alt' => ''],
            ]),
            Field::number('columns')->default(3),
            Field::unit('gap', ['px', 'em'])->default(['value' => 12, 'unit' => 'px']),
            Field::select('ratio', ['' => 'Original', '1/1' => 'Square', '4/3' => '4:3', '16/9' => '16:9'])->default('1/1'),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::sides('border_radius', ['px', '%']),
        ];
    }

    public function css(string $selector): array
    {
        $gap = $this->node->setting('content', 'gap');
        $g = is_array($gap) && ($gap['value'] ?? null) !== null && $gap['value'] !== '' ? $gap['value'].($gap['unit'] ?? 'px') : '12px';
        $cols = max(1, (int) ($this->node->setting('content', 'columns') ?: 3));
        $ratio = $this->node->setting('content', 'ratio');

        $rules = [
            $selector => ['display' => 'grid', 'grid-template-columns' => "repeat({$cols},1fr)", 'gap' => $g],
            '@mobile' => [$selector => ['grid-template-columns' => 'repeat(2,1fr)']],
        ];
        if ($ratio) {
            $rules["{$selector} :where(img)"] = ['aspect-ratio' => $ratio, 'object-fit' => 'cover', 'width' => '100%'];
        }

        return $rules;
    }
}
