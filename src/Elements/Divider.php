<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Divider extends Element
{
    public static string $icon = 'divider';

    public static function view(): string
    {
        return 'buildr::elements.divider';
    }

    public static function contentFields(): array
    {
        return [
            Field::select('style', ['solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted'])->default('solid')
                ->buttons(['solid' => 'line-solid', 'dashed' => 'line-dashed', 'dotted' => 'line-dotted']),
            Field::unit('weight', ['px'])->default(['value' => 1, 'unit' => 'px']),
            Field::unit('width', ['px', '%'])->responsive()->default(['value' => 100, 'unit' => '%']),
            Field::select('align', ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])->default('center')
                ->buttons(['left' => 'h-start', 'center' => 'h-center', 'right' => 'h-end']),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::color('color'),
        ];
    }

    public function css(string $selector): array
    {
        $c = $this->node->settings('content');
        $s = $this->node->settings('style');
        $weight = ($c['weight']['value'] ?? 1).($c['weight']['unit'] ?? 'px');

        return [
            $selector => array_filter([
                'border' => 'none',
                'border-top' => trim($weight.' '.($c['style'] ?? 'solid').' '.($s['color'] ?? 'currentColor')),
                'margin-inline' => match ($c['align'] ?? 'center') {
                    'left' => '0 auto',
                    'right' => 'auto 0',
                    default => 'auto',
                },
            ]),
        ];
    }
}
