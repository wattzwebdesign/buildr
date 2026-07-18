<?php

namespace Buildr\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeBlockCommand extends Command
{
    protected $signature = 'buildr:make-block {name : Class name, e.g. Hero}';

    protected $description = 'Scaffold a per-site coded section block (schema class + Blade view)';

    public function handle(): int
    {
        $class = Str::studly($this->argument('name'));
        $key = Str::snake($class);

        $classPath = app_path("Blocks/{$class}.php");
        $viewPath = resource_path("views/blocks/{$key}.blade.php");

        if (file_exists($classPath)) {
            $this->error("{$classPath} already exists.");

            return self::FAILURE;
        }

        @mkdir(dirname($classPath), 0755, true);
        @mkdir(dirname($viewPath), 0755, true);

        file_put_contents($classPath, <<<PHP
<?php

namespace App\Blocks;

use Buildr\Elements\Element;
use Buildr\Fields\Field;

class {$class} extends Element
{
    public static string \$group = 'sections';

    public static function view(): string
    {
        return 'blocks.{$key}';
    }

    public static function contentFields(): array
    {
        return [
            Field::text('heading')->required(),
        ];
    }
}

PHP);

        file_put_contents($viewPath, <<<BLADE
{{-- {$class} block — design lives here, content comes from the schema. --}}
<section class="{{ \$node->cssId() }}">
    <h2>{{ \$heading }}</h2>
</section>

BLADE);

        $this->info("Created {$classPath}");
        $this->info("Created {$viewPath}");
        $this->comment("Register it in config/buildr.php under 'blocks' => [App\\Blocks\\{$class}::class]");

        return self::SUCCESS;
    }
}
