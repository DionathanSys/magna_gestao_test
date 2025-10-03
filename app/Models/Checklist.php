<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checklist extends Model
{
    protected $casts = [
        'itens_verificados' => 'array',
        'itens_corrigidos'  => 'array',
        'pendencias'        => 'array',
        'anexos'            => 'array',
        'active'            => 'boolean',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
