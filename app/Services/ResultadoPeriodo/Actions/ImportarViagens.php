<?php

namespace App\Services\ResultadoPeriodo\Actions;

use App\Enum\StatusDiversosEnum;
use App\Models;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ImportarViagens
{
    protected Models\ResultadoPeriodo $resultadoPeriodo;
    protected Collection $viagens;

    public function __construct(int $resultadoPeriodoId, protected bool $considerarPeriodo = true)
    {
        $this->resultadoPeriodo = Models\ResultadoPeriodo::findOrFail($resultadoPeriodoId);
    }

    public function handle()
    {
        if ($this->resultadoPeriodo->status !== StatusDiversosEnum::PENDENTE->value) {
            Log::info('Resultado período não está em status PENDENTE. Importação de viagens ignorada.', [
                'metodo' => __METHOD__,
                'resultado_periodo_id' => $this->resultadoPeriodo->id,
                'status_atual' => $this->resultadoPeriodo->status,
            ]);
            return;
        }

        $this->getViagensSemVinculo();

        if ($this->viagens->isEmpty()) {
            Log::info('Nenhuma viagem para importar encontrada.', [
                'metodo' => __METHOD__,
                'veiculo_id' => $this->resultadoPeriodo->veiculo_id,
                'data_inicial' => $this->resultadoPeriodo->data_inicial,
            ]);
        }

        $this->vincularViagens();
    }

    private function getViagensSemVinculo(): void
    {
        $query = Models\Viagem::query()
            ->select('id')
            ->where('veiculo_id', $this->resultadoPeriodo->veiculo_id)
            ->whereNull('resultado_periodo_id');

        if ($this->considerarPeriodo) {
            $query->whereBetween('data_competencia', [
                $this->resultadoPeriodo->data_inicial,
                $this->resultadoPeriodo->data_final,
            ]);
        }

        $this->viagens = $query->get();
    }

    private function vincularViagens(): void
    {
        $viagemIds = $this->viagens->pluck('id')->toArray();

        if (empty($viagemIds)) {
            return;
        }

        Models\Viagem::query()
            ->whereIn('id', $viagemIds)
            ->update([
                'resultado_periodo_id' => $this->resultadoPeriodo->id,
                'updated_at' => now(),
            ]);
    }
}
