<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompraPedidoItem extends Model
{
    protected $table = 'compra_pedido_itens';

    protected $casts = [
        'quantidade_pedida' => 'decimal:4',
        'quantidade_recebida' => 'decimal:4',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(CompraPedido::class, 'compra_pedido_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(EstoqueProduto::class, 'estoque_produto_id');
    }

    public function ordemItens(): HasMany
    {
        return $this->hasMany(CompraOrdemItem::class, 'compra_pedido_item_id');
    }
}
