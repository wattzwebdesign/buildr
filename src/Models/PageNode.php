<?php

namespace Buildr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageNode extends Model
{
    protected $table = 'buildr_nodes';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'visible' => 'boolean',
        'is_draft' => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    /** Settings for one tab: content / style / advanced. */
    public function settings(string $tab): array
    {
        return $this->data[$tab] ?? [];
    }

    public function setting(string $tab, string $key, mixed $default = null): mixed
    {
        return $this->data[$tab][$key] ?? $default;
    }

    /** Unique, stable CSS hook for this node. */
    public function cssId(): string
    {
        return 'b'.$this->id;
    }
}
