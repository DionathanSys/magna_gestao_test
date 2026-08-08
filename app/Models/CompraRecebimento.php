<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompraRecebimento extends Model
{
    protected $table = 'compra_recebimentos';

    protected $casts = [
        'recebido_em' => 'datetime',
    ];

    public function ordem(): BelongsTo
    {
        return $this->belongsTo(CompraOrdem::class, 'compra_ordem_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(CompraRecebimentoItem::class, 'compra_recebimento_id');
    }
}
