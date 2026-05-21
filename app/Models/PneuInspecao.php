<?php

namespace App\Models;

use App\Enum\Pneu\ResultadoInspecaoPneuEnum;
use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Services\Pneus\PneuInspecaoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PneuInspecao extends Model
{
    protected $table = 'pneu_inspecoes';

    protected $casts = [
        'data_inspecao' => 'date',
        'apto_recapagem' => 'boolean',
        'anexos' => 'array',
        'tipo' => TipoInspecaoPneuEnum::class,
        'resultado' => ResultadoInspecaoPneuEnum::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $inspecao): void {
            if (
                filled($inspecao->sulco_interno)
                && blank($inspecao->sulco_centro)
                && blank($inspecao->sulco_externo)
            ) {
                $inspecao->sulco_centro = $inspecao->sulco_interno;
                $inspecao->sulco_externo = $inspecao->sulco_interno;
            }

            if (! $inspecao->pneu_ciclo_id && $inspecao->pneu) {
                $inspecao->pneu_ciclo_id = $inspecao->pneu->cicloAtual?->id;
            }
        });

        static::saved(function (self $inspecao): void {
            (new PneuInspecaoService)->syncResultado($inspecao->loadMissing('pneu'));
        });
    }

    public function pneu(): BelongsTo
    {
        return $this->belongsTo(Pneu::class);
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(PneuCiclo::class, 'pneu_ciclo_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function posicaoVeiculo(): BelongsTo
    {
        return $this->belongsTo(PneuPosicaoVeiculo::class, 'pneu_posicao_veiculo_id');
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }
}
