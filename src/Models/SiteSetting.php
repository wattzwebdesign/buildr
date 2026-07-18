<?php

namespace Buildr\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'buildr_settings';

    protected $guarded = [];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Values are stored as ['v' => ...] so scalars and arrays round-trip
     * through the json column uniformly.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row ? ($row->value['v'] ?? $default) : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => ['v' => $value]]);
    }
}
