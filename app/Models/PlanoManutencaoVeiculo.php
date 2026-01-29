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

    public function execucoes()
    {
        return $this->hasMany(PlanoManutencaoOrdemServico::class, 'plano_preventivo_id', 'plano_preventivo_id')
            ->where('veiculo_id', $this->veiculo_id)
            ->orderBy('km_execucao', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public function getUltimaExecucaoAttribute()
    {
        if (!isset($this->veiculo_id) || !isset($this->plano_preventivo_id)) {
            return null;
        }

        return PlanoManutencaoOrdemServico::query()
            ->where('veiculo_id', $this->veiculo_id)
            ->where('plano_preventivo_id', $this->plano_preventivo_id)
            ->orderBy('km_execucao', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function proximaExecucao(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $ultimaExecucao = $this->ultimaExecucao;
                $kmUltimaExecucao = $ultimaExecucao?->km_execucao ?? 0;
                return $kmUltimaExecucao + $this->planoPreventivo->intervalo;
            }
        );
    }

    public function quilometragemRestante(): Attribute
    {
        return Attribute::make(
            get: fn(): float => $this->proxima_execucao - $this->veiculo->quilometragem_atual
        );
    }
}
