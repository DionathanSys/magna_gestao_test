<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstoqueMovimento extends Model
{
    protected $table = 'estoque_movimentos';

    protected $casts = [
        'data_movimento' => 'date',
        'quantidade_entrada' => 'decimal:4',
        'quantidade_saida' => 'decimal:4',
        'saldo_apos_movimento' => 'decimal:4',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(EstoqueProduto::class, 'estoque_produto_id');
    }

    public function importLog(): BelongsTo
    {
        return $this->belongsTo(ImportLog::class);
    }
}
