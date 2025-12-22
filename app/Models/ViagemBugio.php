<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViagemBugio extends Model
{
    protected $table = 'viagens_bugio';

    protected $casts = [
        'destinos' => 'array',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documentos(): BelongsTo
    {
        return $this->belongsTo(DocumentoFrete::class, 'documento_frete_id');
    }

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class, 'viagem_id');
    }

}
