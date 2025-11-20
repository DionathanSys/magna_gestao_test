<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoPeriodo extends Model
{
    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function tipoVeiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVeiculo::class, 'tipo_veiculo_id');
    }
}
