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

    public function css(string $selector): array
    {
        $full = (bool) $this->node->setting('content', 'full_width');

        return [
            $selector => [
                'display' => 'inline-block',
                'width' => $full ? '100%' : 'max-content',
                'justify-self' => $full ? 'stretch' : 'start',
                'text-decoration' => 'none',
                'text-align' => 'center',
            ],
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
