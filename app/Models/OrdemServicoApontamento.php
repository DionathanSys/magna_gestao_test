<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrdemServicoApontamento extends Model
{
    protected $table = 'ordem_servico_apontamentos';

    protected $casts = [
        'iniciado_em' => 'datetime',
        'encerrado_em' => 'datetime',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function itens(): BelongsToMany
    {
        return $this->belongsToMany(
            ItemOrdemServico::class,
            'ordem_servico_apontamento_itens',
            'ordem_servico_apontamento_id',
            'item_ordem_servico_id'
        )->withTimestamps();
    }
}
