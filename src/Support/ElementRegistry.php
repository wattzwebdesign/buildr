<?php

namespace Buildr\Support;

use Buildr\Elements\Element;
use InvalidArgumentException;

class ElementRegistry
{
    /** @var array<string, class-string<Element>> */
    private array $elements = [];

    /** @param class-string<Element> $class */
    public function register(string $class): void
    {
        if (! is_subclass_of($class, Element::class)) {
            throw new InvalidArgumentException("{$class} must extend ".Element::class);
        }

        $this->elements[$class::key()] = $class;
    }

    /** @param array<int, class-string<Element>> $classes */
    public function registerMany(array $classes): void
    {
        foreach ($classes as $class) {
            $this->register($class);
        }
    }

    /** @return class-string<Element> */
    public function get(string $key): string
    {
        return $this->elements[$key]
            ?? throw new InvalidArgumentException("Unknown Buildr element [{$key}]");
    }

    public function has(string $key): bool
    {
        return isset($this->elements[$key]);
    }

    /** Full schema payload for the editor's library panel. */
    public function schemas(): array
    {
        return array_map(fn (string $class) => $class::schema(), array_values($this->elements));
    }
}
