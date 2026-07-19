<?php

namespace Buildr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'buildr_media';

    protected $guarded = [];

    public function url(): string
    {
        return Storage::disk(config('buildr.media_disk', 'public'))->url($this->path);
    }
}
