<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoManutencaoOrdemServico extends Model
{
    protected $table = 'planos_manutencao_ordem_servico';

    public function planoPreventivo(): BelongsTo
    {
        return $this->belongsTo(PlanoPreventivo::class, 'plano_preventivo_id');
    }

    public function planoPreventivoVinculado(): BelongsTo
    {
        return $this->belongsTo(PlanoManutencaoVeiculo::class, 'plano_preventivo_id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

}
