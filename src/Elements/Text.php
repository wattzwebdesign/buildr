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
            Field::richtext('body')->required()->default('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>'),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('color'),
            Field::unit('font_size', ['px', 'em', 'rem'])->responsive(),
            Field::unit('line_height', ['', 'px', 'em']),
            Field::select('text_align', ['left' => 'Left', 'center' => 'Center', 'right' => 'Right', 'justify' => 'Justify'])->responsive()
                ->buttons(['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right', 'justify' => 'text-justify']),
            Field::color('link_color')->states(),
        ];
    }
}
