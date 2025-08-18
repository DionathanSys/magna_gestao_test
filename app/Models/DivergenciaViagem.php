<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DivergenciaViagem extends Model
{
    protected $table = 'divergencias_viagem';

    public function viagem()
    {
        return $this->belongsTo(Viagem::class, 'viagem_id');
    }
}
