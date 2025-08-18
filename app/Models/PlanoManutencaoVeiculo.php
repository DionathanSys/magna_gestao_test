<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PlanoManutencaoVeiculo extends Model
{
    protected $table = 'planos_manutencao_veiculo';

    protected $appends = ['ultima_execucao', 'proxima_execucao', 'quilometragem_restante'];

    public function planoPreventivo(): BelongsTo
    {
        return $this->belongsTo(PlanoPreventivo::class, 'plano_preventivo_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function ultimaExecucao(): Attribute
    {
        return Attribute::make(
            get: fn(): ?PlanoManutencaoOrdemServico => $this->hasOne(PlanoManutencaoOrdemServico::class, 'plano_preventivo_id', 'plano_preventivo_id')
                ->where('veiculo_id', $this->veiculo_id)
                ->latest()
                ->first()
        );
    }

    public function proximaExecucao(): Attribute
    {
        return Attribute::make(
            get: fn(): float => ($this->ultima_execucao->km_execucao ?? 0) + $this->planoPreventivo->intervalo
        );
    }

    public function quilometragemRestante(): Attribute
    {
        return Attribute::make(
            get: fn(): float => $this->proxima_execucao - $this->veiculo->kmAtual->quilometragem
        );
    }
}
