<?php

namespace App\Jobs;

use App\Enum\StatusDiversosEnum;
use App\Models;
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

    public function handle(): void
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

        $resultadoPeriodo = Models\ResultadoPeriodo::query()
            ->where('veiculo_id', $model->veiculo_id)
            ->where('status', StatusDiversosEnum::PENDENTE->value)
            ->orderByDesc('data_fim')
            ->orderByDesc('id')
            ->first();

        if (! $resultadoPeriodo) {
            Log::info('Nenhum Resultado Período pendente encontrado para vincular.', [
                'metodo' => __METHOD__,
                'veiculo_id' => $model->veiculo_id,
            ]);

            return;
        }

        $model->update([
            'resultado_periodo_id' => $resultadoPeriodo->id,
        ]);

        Log::info('Registro vinculado ao resultado período em aberto.', [
            'metodo' => __METHOD__,
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'resultado_periodo_id' => $resultadoPeriodo->id,
        ]);
    }
}
