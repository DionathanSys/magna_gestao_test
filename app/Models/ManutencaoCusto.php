<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManutencaoCusto extends Model
{
    public function resultadoPeriodo(): BelongsTo
    {
        return $this->belongsTo(ResultadoPeriodo::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }
}
