<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnotacaoViagem extends Model
{
    protected $table = 'anotacoes_viagem';

    public function viagem()
    {
        return $this->belongsTo(Viagem::class, 'viagem_id');
    }
}
