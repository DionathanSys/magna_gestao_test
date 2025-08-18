<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoFrete extends Model
{
    protected $table = 'documentos_frete';

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class, 'documento_transporte', 'documento_transporte');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class, 'integrado_id');
    }
}
