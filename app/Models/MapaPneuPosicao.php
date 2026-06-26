<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapaPneuPosicao extends Model
{
    protected $table = 'mapa_pneu_posicoes';

    public function mapa(): BelongsTo
    {
        return $this->belongsTo(MapaPneu::class, 'mapa_pneu_id');
    }

    public function posicoesVeiculo(): HasMany
    {
        return $this->hasMany(PneuPosicaoVeiculo::class, 'mapa_pneu_posicao_id');
    }
}
