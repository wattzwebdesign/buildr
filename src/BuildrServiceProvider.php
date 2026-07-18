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
