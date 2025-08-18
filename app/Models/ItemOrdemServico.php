<?php

namespace App\Models;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ItemOrdemServico extends Model
{
    protected $table = 'itens_ordem_servico';

    protected $casts = [
        'status' => StatusOrdemServicoEnum::class,
    ];

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function veiculo(): HasOneThrough
    {
        return $this->hasOneThrough(Veiculo::class, OrdemServico::class, 'id', 'id', 'ordem_servico_id', 'veiculo_id');
    }

    public function planoPreventivo(): BelongsTo
    {
        return $this->belongsTo(PlanoPreventivo::class, 'plano_preventivo_id');
    }

    public function agendamento(): HasOneThrough
    {
        return $this->hasOneThrough(Agendamento::class, OrdemServico::class, 'id', 'ordem_servico_id', 'ordem_servico_id', 'id');
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }



}
