<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conserto extends Model
{

    protected $casts = [
        'garantia' => 'boolean',
    ];

    public function pneu(): BelongsTo
    {
        return $this->belongsTo(Pneu::class);
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }
}
