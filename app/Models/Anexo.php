<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anexo extends Model
{
    protected $casts = [
        'attachments' => 'array',
    ];
}
