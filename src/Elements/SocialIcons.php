<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class SocialIcons extends Element
{
    public static string $icon = 'share';

    public static function view(): string
    {
        return 'buildr::elements.social_icons';
    }

    public static function contentFields(): array
    {
        return [
            Field::repeater('accounts', [
                Field::select('platform', [
                    'facebook' => 'Facebook', 'instagram' => 'Instagram', 'x-twitter' => 'X / Twitter',
                    'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'google' => 'Google',
                ])->default('facebook'),
                Field::text('url')->default('#'),
            ])->default([
                ['platform' => 'facebook', 'url' => '#'],
                ['platform' => 'instagram', 'url' => '#'],
            ]),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::unit('size', ['px', 'em'])->default(['value' => 20, 'unit' => 'px']),
            Field::unit('gap', ['px', 'em'])->default(['value' => 10, 'unit' => 'px']),
            Field::color('color'),
            Field::color('background'),
            Field::select('shape', ['' => 'None', 'circle' => 'Circle', 'rounded' => 'Rounded'])->default('circle')
                ->buttons(['' => 'ban', 'circle' => 'shape-circle', 'rounded' => 'shape-rounded']),
        ];
    }

    public function css(string $selector): array
    {
        $u = fn ($v, $d) => is_array($v) && ($v['value'] ?? null) !== null && $v['value'] !== '' ? $v['value'].($v['unit'] ?? 'px') : $d;
        $shape = $this->node->setting('style', 'shape');
        $hasShape = in_array($shape, ['circle', 'rounded'], true);

        return [
            $selector => ['display' => 'inline-flex', 'gap' => $u($this->node->setting('style', 'gap'), '10px')],
            "{$selector} :where(a)" => array_filter([
                'display' => 'grid', 'place-items' => 'center',
                'color' => $this->node->setting('style', 'color') ?: ($hasShape ? '#fff' : '#1f2933'),
                'background' => $hasShape ? ($this->node->setting('style', 'background') ?: '#1f2933') : null,
                'width' => $hasShape ? 'calc('.$u($this->node->setting('style', 'size'), '20px').' * 1.9)' : null,
                'height' => $hasShape ? 'calc('.$u($this->node->setting('style', 'size'), '20px').' * 1.9)' : null,
                'border-radius' => $shape === 'circle' ? '999px' : ($shape === 'rounded' ? '8px' : null),
            ]),
            "{$selector} :where(svg)" => ['width' => $u($this->node->setting('style', 'size'), '20px'), 'height' => $u($this->node->setting('style', 'size'), '20px')],
        ];
    }
}
