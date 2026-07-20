<?php

namespace Buildr;

use Buildr\Console\InstallCommand;
use Buildr\Console\MakeBlockCommand;
use Buildr\DynamicTags\TagRegistry;
use Buildr\Support\ElementRegistry;
use Illuminate\Support\ServiceProvider;

class BuildrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/buildr.php', 'buildr');

        $this->app->singleton(ElementRegistry::class, function ($app) {
            $registry = new ElementRegistry();
            // Core roster lives HERE, not in the publishable config — a
            // site's published config must never pin/hide core elements
            // added by package updates. Config lists per-site EXTRAS only.
            $registry->registerMany([
                \Buildr\Elements\Container::class,
                \Buildr\Elements\Heading::class,
                \Buildr\Elements\Text::class,
                \Buildr\Elements\Image::class,
                \Buildr\Elements\Button::class,
                \Buildr\Elements\Divider::class,
                \Buildr\Elements\Spacer::class,
                \Buildr\Elements\Video::class,
                \Buildr\Elements\GoogleMap::class,
                \Buildr\Elements\Html::class,
                \Buildr\Elements\StarRating::class,
                \Buildr\Elements\IconBox::class,
                \Buildr\Elements\IconList::class,
                \Buildr\Elements\SocialIcons::class,
                \Buildr\Elements\Accordion::class,
                \Buildr\Elements\Tabs::class,
                \Buildr\Elements\Gallery::class,
                \Buildr\Elements\Form::class,
            ]);
            $registry->registerMany(config('buildr.elements', []));
            $registry->registerMany(config('buildr.blocks', []));

            return $registry;
        });

        $this->app->singleton(TagRegistry::class, function ($app) {
            $registry = new TagRegistry();
            $registry->registerDefaults();

            foreach (config('buildr.tags', []) as $name => $resolver) {
                $registry->register($name, $resolver);
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'buildr');

        \Livewire\Livewire::component('buildr.dashboard', \Buildr\Http\Livewire\Dashboard::class);
        \Livewire\Livewire::component('buildr.editor', \Buildr\Http\Livewire\Editor::class);

        // Public asset, deliberately outside the admin middleware group —
        // placeholder images render on the public site too.
        \Illuminate\Support\Facades\Route::get('/buildr-assets/placeholder.svg', function () {
            return response(file_get_contents(__DIR__.'/../resources/assets/placeholder.svg'), 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        })->name('buildr.placeholder');

        \Illuminate\Support\Facades\Route::get('/buildr-assets/icons.json', function () {
            $payload = '{"icons":'.file_get_contents(__DIR__.'/../resources/icons/lucide.json')
                .',"meta":'.file_get_contents(__DIR__.'/../resources/icons/lucide-meta.json').'}';

            return response($payload, 200, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        })->name('buildr.icons');

        \Illuminate\Support\Facades\Route::middleware(config('buildr.middleware', ['web']))
            ->post('/buildr-form/{node}', \Buildr\Http\FormSubmissionController::class)
            ->name('buildr.form.submit');

        \Illuminate\Support\Facades\Route::middleware(config('buildr.admin_middleware', ['web']))
            ->prefix(config('buildr.admin_path', 'buildr'))
            ->group(function () {
                \Illuminate\Support\Facades\Route::get('/', fn () => redirect()->route('buildr.pages'));
                \Illuminate\Support\Facades\Route::get('/pages', \Buildr\Http\Livewire\Dashboard::class)->name('buildr.pages');
                \Illuminate\Support\Facades\Route::get('/pages/{page}/edit', \Buildr\Http\Livewire\Editor::class)->name('buildr.edit');
                \Illuminate\Support\Facades\Route::get('/media', \Buildr\Http\Livewire\MediaLibrary::class)->name('buildr.media');
                \Illuminate\Support\Facades\Route::get('/assets/admin.css', function () {
                    return response(file_get_contents(__DIR__.'/../resources/assets/admin.css'), 200, [
                        'Content-Type' => 'text/css',
                        'Cache-Control' => 'public, max-age=3600',
                    ]);
                })->name('buildr.admin.css');
            });

        if ($prefix = config('buildr.route')) {
            \Illuminate\Support\Facades\Route::middleware(config('buildr.middleware', ['web']))
                ->get(rtrim($prefix, '/').'/{slug?}', \Buildr\Http\PageController::class)
                ->where('slug', '[a-z0-9\-\/]*')
                ->name('buildr.page');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/buildr.php' => config_path('buildr.php'),
            ], 'buildr-config');

            $this->commands([
                InstallCommand::class,
                MakeBlockCommand::class,
            ]);
        }
    }
}
