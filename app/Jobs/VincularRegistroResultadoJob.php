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

    protected $model;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $modelId, protected string $modelClass)
    {
        $this->model = $this->modelClass::find($this->modelId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("Iniciando vinculação do registro resultado para {$this->modelClass} ID: {$this->modelId}", [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'model' => $this->model,
        ]);

        if (!$this->model) {
            Log::error("Registro não encontrado para vinculação do resultado.", [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
            ]);
            return;
        }

        $resultadoPeriodo = Models\ResultadoPeriodo::query()
            ->where('veiculo_id', $this->model->veiculo_id)
            ->where('status', StatusDiversosEnum::PENDENTE)
            ->first();

        if (!$resultadoPeriodo) {
            Log::info("Nenhum Resultado Período pendente encontrado para vincular.", [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'veiculo_id' => $this->model->veiculo_id,
            ]);
            return;
        }

        $this->model->update([
            'resultado_periodo_id' => $resultadoPeriodo->id,
        ]);

    }
}
