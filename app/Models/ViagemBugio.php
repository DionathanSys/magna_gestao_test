<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViagemBugio extends Model
{
    protected $table = 'viagens_bugio';

    protected $casts = [
        'destinos' => 'array',
    ];
}
