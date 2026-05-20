<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PneuPosicaoVeiculo extends Model
{
    protected $table = 'pneu_posicao_veiculo';

    protected $appends = ['km_rodado'];

    public function pneu(): BelongsTo
    {
        return $this->belongsTo(Pneu::class, 'pneu_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(PneuCiclo::class, 'pneu_ciclo_id');
    }

    public function kmPercorrido(): Attribute
    {
        return Attribute::get(
            fn () => ($this->km_final ?? 0) - ($this->km_inicial ?? 0)
        );
    }

    protected function kmRodado(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->km_inicial
                ? (($this->veiculo?->kmAtual?->quilometragem ?? 0) - $this->km_inicial)
                : 0
        );
    }

    public function scopeAplicados($query)
    {
        return $query->whereNotNull('pneu_id')
            ->whereNotNull('veiculo_id');
    }
}
