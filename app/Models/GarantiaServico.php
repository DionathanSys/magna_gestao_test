<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GarantiaServico extends Model
{
    protected $casts = [
        'controla_posicao' => 'boolean',
        'em_garantia' => 'boolean',
        'data_execucao' => 'datetime',
        'data_execucao_anterior' => 'datetime',
        'km_execucao' => 'integer',
        'km_execucao_anterior' => 'integer',
        'km_durabilidade' => 'integer',
        'dias_durabilidade' => 'integer',
        'garantia_km_aplicada' => 'integer',
        'garantia_dias_aplicada' => 'integer',
    ];

    public function itemOrdemServico(): BelongsTo
    {
        return $this->belongsTo(ItemOrdemServico::class, 'item_ordem_servico_id');
    }

    public function itemOrdemServicoAnterior(): BelongsTo
    {
        return $this->belongsTo(ItemOrdemServico::class, 'item_ordem_servico_anterior_id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function ordemServicoAnterior(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_anterior_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }
}
