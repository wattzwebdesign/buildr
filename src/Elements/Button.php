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
        $stored = $this->node->setting('content', 'full_width');
        $per = is_array($stored) ? $stored : ['desktop' => $stored];
        $desktopFull = (bool) ($per['desktop'] ?? false);

        $size = fn (bool $full) => [
            'width' => $full ? '100%' : 'max-content',
            'justify-self' => $full ? 'stretch' : 'start',
        ];

        $rules = [
            $selector => $size($desktopFull) + [
                'display' => 'inline-block',
                'text-decoration' => 'none',
                'text-align' => 'center',
            ],
        ];

        foreach (['tablet', 'mobile'] as $device) {
            if (isset($per[$device]) && (bool) $per[$device] !== $desktopFull) {
                $rules['@'.$device][$selector] = $size((bool) $per[$device]);
            }
        }

        return $rules;
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
