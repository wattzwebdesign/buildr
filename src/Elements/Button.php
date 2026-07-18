<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Button extends Element
{
    public static string $icon = 'button';

    public static function view(): string
    {
        return 'buildr::elements.button';
    }

    public static function contentFields(): array
    {
        return [
            Field::text('label')->required()->default('Click here'),
            Field::link('link'),
            Field::toggle('full_width')->responsive(),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('color')->label('Text color')->states(),
            Field::color('background')->states(),
            Field::unit('font_size', ['px', 'em', 'rem']),
            Field::select('font_weight', array_combine(range(100, 900, 100), range(100, 900, 100))),
            Field::sides('border_radius', ['px', '%']),
            Field::sides('padding', ['px', 'em']),
        ];
    }
}
