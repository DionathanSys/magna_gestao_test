<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resultado extends Model
{
    public function gestor(): BelongsTo
    {
        return $this->belongsTo(Gestor::class, 'gestor_id');
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class, 'indicador_id');
    }
}
