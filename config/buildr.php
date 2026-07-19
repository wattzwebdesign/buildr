<?php

return [

    /*
    | Route prefix for the public catch-all that resolves published pages by
    | slug, and the middleware stack applied to it. Set 'route' to null to
    | disable the catch-all and mount pages yourself.
    */
    'route' => '/',
    'middleware' => ['web'],

    /*
    | Admin editor mount point and middleware. Add your auth middleware per
    | site, e.g. ['web', 'auth']. No role gating inside Buildr itself.
    */
    'admin_path' => 'buildr',
    'admin_middleware' => ['web'],

    /*
    | Responsive breakpoints (max-width, px) used by the style compiler for
    | tablet/mobile values on responsive controls.
    */
    'breakpoints' => [
        'tablet' => 1024,
        'mobile' => 640,
    ],



    /*
    | EXTRA element classes for this site — core elements ship with the
    | package and register automatically, so package updates can add new
    | ones without touching this file. Coded section blocks go in 'blocks'.
    */
    'elements' => [],

    'blocks' => [],

    /*
    | Disk for media library uploads (needs a public URL; run
    | `php artisan storage:link` for the default 'public' disk).
    */
    'media_disk' => 'public',

    /*
    | Per-site dynamic tags: 'name' => resolver. Resolvers may be callables
    | or container-resolvable invokable class names.
    | Example: 'review_count' => fn () => Review::count(),
    */
    'tags' => [],
];
