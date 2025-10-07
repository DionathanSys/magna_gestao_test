<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CargaViagem extends Model
{
    protected $table = 'cargas_viagem';

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class, 'viagem_id');
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class, 'integrado_id');
    }

    public function complementos(): BelongsTo
    {
        return $this->belongsTo(ViagemComplemento::class, 'documento_transporte', 'documento_transporte');
    }

    public function comentarios(): HasManyThrough
    {
        return $this->hasManyThrough(Comentario::class, Viagem::class, 'id', 'viagem_id', 'viagem_id', 'id');
    }

}
