<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManutencaoLancamento extends Model
{
    use SoftDeletes;

    protected $casts = [
        'data_negociacao' => 'date',
        'quantidade' => 'decimal:4',
        'valor_total_centavos' => 'integer',
        'valor_unitario_centavos' => 'integer',
        'dispensado_vinculo' => 'boolean',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function importLog(): BelongsTo
    {
        return $this->belongsTo(ImportLog::class);
    }

    public function vinculadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vinculado_por');
    }

    public function dispensadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensado_por');
    }
}
