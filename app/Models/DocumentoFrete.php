<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enum\Frete\TipoDocumentoEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoFrete extends Model
{
    protected $table = 'documentos_frete';

    protected $casts = [
        'tipo_documento' => TipoDocumentoEnum::class,
        'valor_total' => MoneyCast::class,
        'valor_icms' => MoneyCast::class,
    ];

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
