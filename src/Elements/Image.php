<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Image extends Element
{
    public static string $icon = 'image';

    public static function view(): string
    {
        return 'buildr::elements.image';
    }

    public static function contentFields(): array
    {
        return [
            Field::media('src')->label('Image')->required()->default('/buildr-assets/placeholder.svg'),
            Field::text('alt')->label('Alt text')->default('Placeholder image'),
            Field::text('caption'),
            Field::link('link'),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::unit('width', ['px', '%', 'vw'])->responsive(),
            Field::unit('max_width', ['px', '%'])->responsive(),
            Field::unit('height', ['px', 'vh'])->responsive(),
            Field::select('object_fit', ['' => 'Default', 'cover' => 'Cover', 'contain' => 'Contain']),
            Field::sides('border_radius', ['px', '%'])->section('Border'),
            Field::select('shadow', ['' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'])->section('Effects'),
        ];
    }
}
