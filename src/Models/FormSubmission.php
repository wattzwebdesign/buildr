<?php

namespace Buildr\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $table = 'buildr_form_submissions';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
