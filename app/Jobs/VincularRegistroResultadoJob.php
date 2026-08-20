<?php

namespace App\Jobs;

use App\Services\ResultadoPeriodo\ResultadoPeriodoVinculoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class VincularRegistroResultadoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $modelId,
        protected string $modelClass,
    ) {}

    public function handle(ResultadoPeriodoVinculoService $vinculoService): void
    {
        Log::debug("Iniciando vinculação do registro resultado para {$this->modelClass} ID: {$this->modelId}", [
            'metodo' => __METHOD__,
            'model_id' => $this->modelId,
        ]);

        $model = $this->modelClass::find($this->modelId);

        if (! $model) {
            Log::error('Registro não encontrado para vinculação do resultado.', [
                'metodo' => __METHOD__,
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
            ]);

            return;
        }

        if ($model->resultado_periodo_id) {
            Log::info('Registro já vinculado a um resultado período. Vinculação ignorada.', [
                'metodo' => __METHOD__,
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'resultado_periodo_id' => $model->resultado_periodo_id,
            ]);

            return;
        }

        try {
            $vinculoService->vincular($model, 'aberto');
        } catch (\RuntimeException $exception) {
            Log::info('Registro não foi vinculado ao resultado período em aberto.', [
                'metodo' => __METHOD__,
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'motivo' => $exception->getMessage(),
            ]);
        }
    }
}
