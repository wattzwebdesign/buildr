<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Spacer extends Element
{
    public static string $icon = 'spacer';

    public static function view(): string
    {
        return 'buildr::elements.spacer';
    }

    public static function contentFields(): array
    {
        return [
            Field::unit('height', ['px', 'em', 'rem', 'vh'])->responsive()
                ->default(['value' => 48, 'unit' => 'px']),
        ];
    }
}
