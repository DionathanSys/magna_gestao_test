<?php

namespace App\Jobs;

use App\Models\ResultadoPeriodo;
use App\Models\Viagem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class VincularViagemResultadoPeriodoJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $viagemId,
        protected string $dataInicio,
        protected string $dataFim
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("Iniciando vinculação de viagem ao resultado período", [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'viagem_id' => $this->viagemId,
            'data_inicio' => $this->dataInicio,
            'data_fim' => $this->dataFim,
        ]);

        $viagem = Viagem::find($this->viagemId);

        if (!$viagem) {
            Log::error("Viagem não encontrada para vinculação ao resultado período.", [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'viagem_id' => $this->viagemId,
            ]);
            return;
        }

        if (!$viagem->veiculo_id) {
            Log::warning("Viagem sem veículo vinculado, não é possível vincular ao resultado período.", [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'viagem_id' => $this->viagemId,
            ]);
            return;
        }

        $resultadoPeriodo = ResultadoPeriodo::query()
            ->where('veiculo_id', $viagem->veiculo_id)
            ->where('data_inicio', $this->dataInicio)
            ->where('data_fim', $this->dataFim)
            ->first();

        if (!$resultadoPeriodo) {
            Log::warning("Nenhum Resultado Período encontrado com os critérios especificados.", [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'veiculo_id' => $viagem->veiculo_id,
                'data_inicio' => $this->dataInicio,
                'data_fim' => $this->dataFim,
            ]);
            return;
        }

        $viagem->update([
            'resultado_periodo_id' => $resultadoPeriodo->id,
        ]);

        Log::info("Viagem vinculada ao resultado período com sucesso.", [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'viagem_id' => $this->viagemId,
            'resultado_periodo_id' => $resultadoPeriodo->id,
        ]);
    }
}
