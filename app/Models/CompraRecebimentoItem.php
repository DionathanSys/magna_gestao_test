<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraRecebimentoItem extends Model
{
    protected $table = 'compra_recebimento_itens';

    protected $casts = [
        'quantidade_recebida' => 'decimal:4',
    ];

    public function recebimento(): BelongsTo
    {
        return $this->belongsTo(CompraRecebimento::class, 'compra_recebimento_id');
    }

    public function ordemItem(): BelongsTo
    {
        return $this->belongsTo(CompraOrdemItem::class, 'compra_ordem_item_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(EstoqueProduto::class, 'estoque_produto_id');
    }
}
