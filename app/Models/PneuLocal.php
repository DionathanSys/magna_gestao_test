<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PneuLocal extends Model
{
    protected $table = 'pneu_locais';

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    public function pneus(): HasMany
    {
        return $this->hasMany(Pneu::class, 'pneu_local_id');
    }
}
