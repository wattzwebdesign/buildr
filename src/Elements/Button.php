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
            Field::text('label')->required()->default('Click Here'),
            Field::link('link')->default(['url' => '#']),
            Field::select('align', [
                'left' => 'Left', 'center' => 'Center', 'right' => 'Right', 'full' => 'Full width',
            ])->default('left')->responsive()
                ->buttons(['left' => 'h-start', 'center' => 'h-center', 'right' => 'h-end', 'full' => 'h-stretch']),
        ];
    }

    public function css(string $selector): array
    {
        $align = $this->node->setting('content', 'align');
        $per = is_array($align) ? $align : ['desktop' => $align];

        if (empty($per['desktop'])) {
            // legacy nodes stored a full_width toggle instead of align
            $legacy = $this->node->setting('content', 'full_width');
            $legacyFull = is_array($legacy) ? (bool) ($legacy['desktop'] ?? false) : (bool) $legacy;
            $per['desktop'] = $legacyFull ? 'full' : 'left';
        }

        $decl = function (string $a): array {
            $pos = match ($a) {
                'center' => 'center', 'right' => 'end', 'full' => 'stretch', default => 'start',
            };

            return [
                'width' => $a === 'full' ? '100%' : 'max-content',
                'justify-self' => $pos,
                'align-self' => $pos,
            ];
        };

        $rules = [
            $selector => $decl($per['desktop']) + [
                'display' => 'inline-block',
                'text-decoration' => 'none',
                'text-align' => 'center',
            ],
        ];

        foreach (['tablet', 'mobile'] as $device) {
            if (! empty($per[$device]) && $per[$device] !== $per['desktop']) {
                $rules['@'.$device][$selector] = $decl($per[$device]);
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
