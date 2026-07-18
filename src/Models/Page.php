<?php

namespace Buildr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $table = 'buildr_pages';

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(PageNode::class, 'page_id')->orderBy('sort');
    }

    public function rootNodes(): HasMany
    {
        return $this->nodes()->whereNull('parent_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class, 'page_id')->latest();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
