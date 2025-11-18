<?php

namespace App\Models;

use App\Jobs\ProcessarAlertasIntegrados;
use App\Services\Carga\CargaService;
use App\Services\Integrado\IntegradoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Log;

class CargaViagem extends Model
{
    protected $table = 'cargas_viagem';

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class, 'viagem_id');
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class, 'integrado_id');
    }

    public function complementos(): BelongsTo
    {
        return $this->belongsTo(ViagemComplemento::class, 'documento_transporte', 'documento_transporte');
    }

    protected static function booted()
    {
        static::created(function (self $model) {
            Log::info('CargaViagem criada', [
                'id' => $model->id, 
                'viagem_id' => $model->viagem_id, 
                'integrado_id' => $model->integrado_id]);

            (new CargaService())->atualizarKmDispersao($model->viagem_id);

            if ($model->integrado_id) {
                $integradoService = new IntegradoService();
                
                if ($integradoService->getIntegradoAlertaById($model->integrado_id)) {
                    // Adiciona ao lote de alertas
                    ProcessarAlertasIntegrados::adicionarCarga($model->id);
                    
                    Log::info('Carga adicionada ao lote de alertas (via cache)', [
                        'carga_id' => $model->id,
                        'integrado_id' => $model->integrado_id,
                    ]);
                }
            }
        });

        static::updated(function (self $model) {
            Log::info('CargaViagem atualizada', ['id' => $model->id, 'viagem_id' => $model->viagem_id, 'integrado_id' => $model->integrado_id]);
        });

        static::deleted(function (self $model) {
            Log::info('CargaViagem removida (deleted)', ['id' => $model->id, 'viagem_id' => $model->viagem_id]);
            (new CargaService())->atualizarKmDispersao($model->viagem_id);
        });
    }

}
