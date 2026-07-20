<?php

namespace Buildr\DynamicTags;

use Buildr\Models\SiteSetting;
use Illuminate\Support\Carbon;

/**
 * Resolves {{tag}} tokens in content at render time, server-side.
 *
 * Supported forms:
 *   {{site.phone}}          — any registered tag
 *   {{date:F j, Y}}         — current date, any PHP/Carbon format string
 *   {{year}}                — shorthand for {{date:Y}}
 *   {{page.title}}          — context tags, resolved against the current page
 */
class TagRegistry
{
    /** @var array<string, callable> */
    private array $tags = [];

    public function register(string $name, callable|string $resolver): void
    {
        $this->tags[$name] = is_string($resolver) ? fn () => app()->call($resolver) : $resolver;
    }

    public function registerDefaults(): void
    {
        foreach (['name', 'phone', 'email', 'address', 'hours'] as $key) {
            $this->register("site.{$key}", fn () => SiteSetting::get($key, ''));
        }

        $this->register('site.phone_link', function () {
            $phone = (string) SiteSetting::get('phone', '');

            return 'tel:'.preg_replace('/[^0-9+]/', '', $phone);
        });

        $this->register('year', fn () => Carbon::now()->format('Y'));
        $this->register('site.url', fn () => url('/'));
        $this->register('page.title', fn (array $ctx = []) => $ctx['page']->title ?? '');
        $this->register('page.slug', fn (array $ctx = []) => $ctx['page']->slug ?? '');
        $this->register('page.url', fn (array $ctx = []) => isset($ctx['page']) ? url($ctx['page']->slug) : '');
    }

    public function resolve(?string $text, array $context = []): ?string
    {
        if ($text === null || ! str_contains($text, '{{')) {
            return $text;
        }

        return preg_replace_callback('/\{\{\s*([a-z0-9_.]+)(?::([^}]+))?\s*\}\}/i', function ($m) use ($context) {
            [$full, $name] = $m;
            $arg = $m[2] ?? null;

            if ($name === 'date') {
                return Carbon::now()->format($arg ?? 'F j, Y');
            }

            if (isset($this->tags[$name])) {
                return (string) ($this->tags[$name])($context);
            }

            return $full; // unknown tags pass through untouched
        }, $text);
    }
}
