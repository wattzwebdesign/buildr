<?php

namespace Buildr\Elements;

use Buildr\Fields\Field;

class Video extends Element
{
    public static string $icon = 'video';

    public static function view(): string
    {
        return 'buildr::elements.video';
    }

    public static function contentFields(): array
    {
        return [
            Field::text('url')->label('Video URL')->help('YouTube, Vimeo, or a direct .mp4 URL'),
            Field::toggle('autoplay')->help('Autoplay forces mute'),
            Field::toggle('loop'),
            Field::toggle('controls')->default(true),
            Field::media('poster')->label('Poster image'),
        ];
    }

    public static function styleFields(): array
    {
        return [
            Field::select('ratio', ['16/9' => '16:9', '4/3' => '4:3', '1/1' => '1:1', '9/16' => '9:16'])->default('16/9'),
            Field::sides('border_radius', ['px', '%']),
        ];
    }

    public function css(string $selector): array
    {
        return [$selector => [
            'aspect-ratio' => $this->node->setting('style', 'ratio') ?: '16/9',
            'width' => '100%',
            'overflow' => 'hidden',
        ], "{$selector} :where(iframe,video)" => [
            'width' => '100%', 'height' => '100%', 'border' => '0', 'display' => 'block', 'object-fit' => 'cover',
        ]];
    }
}
