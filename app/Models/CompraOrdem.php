<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompraOrdem extends Model
{
    use SoftDeletes;

    protected $table = 'compra_ordens';

    protected $casts = [
        'previsao_entrega_em' => 'date',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(CompraPedido::class, 'compra_pedido_id');
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(CompraOrdemItem::class, 'compra_ordem_id');
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(CompraRecebimento::class, 'compra_ordem_id');
    }

    public function atualizarAtendimento(): void
    {
        $this->loadMissing('itens');

        $totalPrevisto = $this->itens->sum(fn (CompraOrdemItem $item): float => (float) $item->quantidade_prevista);
        $totalRecebido = $this->itens->sum(fn (CompraOrdemItem $item): float => (float) $item->quantidade_recebida);

        $status = match (true) {
            $totalPrevisto > 0 && $totalRecebido >= $totalPrevisto => 'atendido',
            $totalRecebido > 0 => 'parcial',
            default => 'aberto',
        };

        $this->forceFill(['status' => $status])->save();
    }
}
