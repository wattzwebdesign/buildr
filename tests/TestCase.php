<?php

namespace Buildr\Tests;

use Buildr\BuildrServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [\Livewire\LivewireServiceProvider::class, BuildrServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->run();
    }

    /** Publish the draft, then render the public output. */
    protected function publishedRender(\Buildr\Models\Page $page): array
    {
        \Buildr\Support\Publisher::publish($page->fresh());

        return $this->app->make(\Buildr\Render\PageRenderer::class)->render($page->fresh());
    }
}
