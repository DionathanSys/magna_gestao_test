<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanoPreventivo extends Model
{
    protected $table = 'planos_preventivo';

    protected $casts = [
        'itens' => 'array',
    ];

    public function veiculos(): HasMany
    {
        return $this->hasMany(PlanoManutencaoVeiculo::class, 'plano_preventivo_id');
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(PlanoManutencaoOrdemServico::class, 'plano_preventivo_id');
    }
}
