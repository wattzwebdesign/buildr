<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Text extends Element
{
    public static string $icon = 'text';

    public static function view(): string
    {
        return 'buildr::elements.text';
    }

    public static function contentFields(): array
    {
        return [
            Field::richtext('body')->required(),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('color'),
            Field::unit('font_size', ['px', 'em', 'rem'])->responsive(),
            Field::unit('line_height', ['', 'px', 'em']),
            Field::select('text_align', ['left' => 'Left', 'center' => 'Center', 'right' => 'Right', 'justify' => 'Justify'])->responsive(),
            Field::color('link_color')->states(),
        ];
    }
}
