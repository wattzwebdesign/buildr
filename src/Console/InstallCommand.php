<?php

namespace Buildr\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'buildr:install';

    protected $description = 'Publish Buildr config, run migrations, and scaffold the app Blocks directory';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'buildr-config']);
        $this->call('migrate');

        if (! is_dir(app_path('Blocks'))) {
            mkdir(app_path('Blocks'), 0755, true);
            $this->info('Created app/Blocks for per-site coded sections.');
        }

        $this->info('Buildr installed. Next: php artisan buildr:make-block Hero');

        return self::SUCCESS;
    }
}
