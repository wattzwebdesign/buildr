<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Heading extends Element
{
    public static string $icon = 'heading';

    public static function view(): string
    {
        return 'buildr::elements.heading';
    }

    public static function contentFields(): array
    {
        return [
            Field::text('text')->required()->default('Heading'),
            Field::select('tag', [
                'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3',
                'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'p' => 'p',
            ])->label('HTML tag')->default('h2'),
            Field::link('link'),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('color'),
            Field::select('font_family', [])->help('Global fonts listed first'),
            Field::unit('font_size', ['px', 'em', 'rem', 'vw'])->responsive(),
            Field::select('font_weight', array_combine(range(100, 900, 100), range(100, 900, 100))),
            Field::unit('line_height', ['', 'px', 'em']),
            Field::unit('letter_spacing', ['px', 'em']),
            Field::select('text_transform', ['none' => 'None', 'uppercase' => 'Uppercase', 'lowercase' => 'Lowercase', 'capitalize' => 'Capitalize']),
            Field::select('text_align', ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])->responsive(),
        ];
    }
}
