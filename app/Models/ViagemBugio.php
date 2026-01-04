<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;

class ViagemBugio extends Model
{
    protected $table = 'viagens_bugio';

    protected $casts = [
        'anexos' => 'array',
        'destinos' => 'array',
        'nro_notas' => 'array',
        'info_adicionais' => 'array',
        'numero_sequencial' => 'integer',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoFrete::class, 'documento_frete_id');
    }

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class, 'viagem_id');
    }

    protected static function booted()
    {
        static::created(function(self $model){
            Log::info('Solicitação Viagem Bugio Criada - ' . __METHOD__, [
                'id' => $model->id,
            ]);

        });

        
    } 
}
