<?php

namespace App\Models;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Services\ViagemBugio\ViagemBugioService;
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

            if($model->info_adicionais['tipo_documento'] == TipoDocumentoEnum::NFS->value){
                $model->update([
                    'nro_documento' => $model->numero_sequencial,
                    'status'        => 'concluido',
                ]);

                $service = new ViagemBugioService();
                $service->createViagemFromBugio($model);

            } elseif (in_array($model->info_adicionais['tipo_documento'], [TipoDocumentoEnum::CTE->value, TipoDocumentoEnum::CTE_COMPLEMENTO->value])){
                $service = new ViagemBugioService();
                $service->solicitarCte($model);
            }


        });

        static::updated(function (self $model) {
            Log::info('Solicitação Viagem Bugio foi editada', [
                'model' => $model->id,
                'mudancas' => $model->getDirty(),
            ]);

            if($model->isDirty('nro_documento')){
                Log::info('Campo nro documento da solicitação foi alterado');
            }
        });


    } 
}
