<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompraPedido extends Model
{
    use SoftDeletes;

    protected $table = 'compra_pedidos';

    public function itens(): HasMany
    {
        return $this->hasMany(CompraPedidoItem::class, 'compra_pedido_id');
    }

    public function ordens(): HasMany
    {
        return $this->hasMany(CompraOrdem::class, 'compra_pedido_id');
    }

    public function atualizarAtendimento(): void
    {
        $this->loadMissing('itens');

        $totalPedido = $this->itens->sum(fn (CompraPedidoItem $item): float => (float) $item->quantidade_pedida);
        $totalRecebido = $this->itens->sum(fn (CompraPedidoItem $item): float => (float) $item->quantidade_recebida);

        $status = match (true) {
            $totalPedido > 0 && $totalRecebido >= $totalPedido => 'atendido',
            $totalRecebido > 0 => 'parcial',
            default => 'aberto',
        };

        $this->forceFill(['status' => $status])->save();
    }
}
