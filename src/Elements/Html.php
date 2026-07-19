<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Html extends Element
{
    public static ?string $label = 'HTML / Embed';
    public static string $icon = 'code';

    public static function view(): string
    {
        return 'buildr::elements.html';
    }

    public static function contentFields(): array
    {
        return [
            Field::code('code')->label('Code')->help('Rendered verbatim — embeds, widgets, tracking snippets.'),
        ];
    }
}
