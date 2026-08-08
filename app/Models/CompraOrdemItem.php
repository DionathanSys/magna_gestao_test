<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompraOrdemItem extends Model
{
    protected $table = 'compra_ordem_itens';

    protected $casts = [
        'quantidade_prevista' => 'decimal:4',
        'quantidade_recebida' => 'decimal:4',
    ];

    public function ordem(): BelongsTo
    {
        return $this->belongsTo(CompraOrdem::class, 'compra_ordem_id');
    }

    public function pedidoItem(): BelongsTo
    {
        return $this->belongsTo(CompraPedidoItem::class, 'compra_pedido_item_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(EstoqueProduto::class, 'estoque_produto_id');
    }

    public function recebimentoItens(): HasMany
    {
        return $this->hasMany(CompraRecebimentoItem::class, 'compra_ordem_item_id');
    }
}
