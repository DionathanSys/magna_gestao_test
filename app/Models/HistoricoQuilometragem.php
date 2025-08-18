<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoQuilometragem extends Model
{
    protected $table = 'historico_quilometragens';

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }
}
