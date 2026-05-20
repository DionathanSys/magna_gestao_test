<?php

namespace App\Models;

use App\Enum\Pneu\StatusCicloPneuEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PneuCiclo extends Model
{
    protected $casts = [
        'status' => StatusCicloPneuEnum::class,
        'data_abertura' => 'date',
        'data_fechamento' => 'date',
    ];

    public function pneu(): BelongsTo
    {
        return $this->belongsTo(Pneu::class);
    }

    public function desenhoPneu(): BelongsTo
    {
        return $this->belongsTo(DesenhoPneu::class, 'desenho_pneu_id');
    }

    public function recapagens(): HasMany
    {
        return $this->hasMany(Recapagem::class, 'pneu_ciclo_id');
    }

    public function consertos(): HasMany
    {
        return $this->hasMany(Conserto::class, 'pneu_ciclo_id');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(HistoricoMovimentoPneu::class, 'pneu_ciclo_id');
    }

    public function inspecoes(): HasMany
    {
        return $this->hasMany(PneuInspecao::class, 'pneu_ciclo_id');
    }
}
