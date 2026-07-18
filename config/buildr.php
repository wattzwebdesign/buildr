<?php

use Buildr\Elements;

return [

    /*
    | Route prefix for the public catch-all that resolves published pages by
    | slug, and the middleware stack applied to it. Set 'route' to null to
    | disable the catch-all and mount pages yourself.
    */
    'route' => '/',
    'middleware' => ['web'],

    /*
    | Responsive breakpoints (max-width, px) used by the style compiler for
    | tablet/mobile values on responsive controls.
    */
    'breakpoints' => [
        'tablet' => 1024,
        'mobile' => 640,
    ],

    /*
    | Core element roster. Per-site coded blocks are auto-discovered from
    | app/Blocks (namespace App\Blocks) and may also be listed in 'blocks'.
    */
    'elements' => [
        Elements\Container::class,
        Elements\Heading::class,
        Elements\Text::class,
        Elements\Image::class,
        Elements\Button::class,
        Elements\Divider::class,
        Elements\Spacer::class,
    ],

    'blocks' => [],

    /*
    | Per-site dynamic tags: 'name' => resolver. Resolvers may be callables
    | or container-resolvable invokable class names.
    | Example: 'review_count' => fn () => Review::count(),
    */
    'tags' => [],
];
