<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class GoogleMap extends Element
{
    public static ?string $key = 'map';
    public static ?string $label = 'Map';
    public static string $icon = 'map';

    public static function view(): string
    {
        return 'buildr::elements.map';
    }

    public static function contentFields(): array
    {
        return [
            Field::text('address')->default('{{site.address}}'),
            Field::number('zoom')->default(14),
            Field::unit('height', ['px', 'vh'])->responsive()->default(['value' => 320, 'unit' => 'px']),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::sides('border_radius', ['px', '%']),
            Field::toggle('grayscale'),
        ];
    }

    public function css(string $selector): array
    {
        $rules = [$selector => ['width' => '100%', 'overflow' => 'hidden'],
            "{$selector} :where(iframe)" => ['width' => '100%', 'height' => '100%', 'border' => '0', 'display' => 'block']];
        if ($this->node->setting('style', 'grayscale')) {
            $rules["{$selector} :where(iframe)"]['filter'] = 'grayscale(1)';
        }

        return $rules;
    }
}
