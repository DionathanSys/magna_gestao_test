<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstoqueProduto extends Model
{
    use SoftDeletes;

    protected $table = 'estoque_produtos';

    protected $casts = [
        'saldo' => 'decimal:4',
        'estoque_minimo' => 'decimal:4',
        'estoque_maximo' => 'decimal:4',
        'valor_reposicao_centavos' => 'integer',
        'custo_total_centavos' => 'integer',
        'ultimo_movimento_em' => 'date',
        'dias_obsolescencia' => 'integer',
        'previsao_consumo_dias' => 'integer',
        'ativo' => 'boolean',
    ];

    public function movimentos(): HasMany
    {
        return $this->hasMany(EstoqueMovimento::class, 'estoque_produto_id');
    }

    public function pedidoItens(): HasMany
    {
        return $this->hasMany(CompraPedidoItem::class, 'estoque_produto_id');
    }

    protected function statusEstoque(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->estoque_minimo !== null && (float) $this->saldo < (float) $this->estoque_minimo) {
                    return 'abaixo_minimo';
                }

                if ($this->estoque_maximo !== null && (float) $this->saldo > (float) $this->estoque_maximo) {
                    return 'acima_maximo';
                }

                return 'normal';
            }
        );
    }
}
