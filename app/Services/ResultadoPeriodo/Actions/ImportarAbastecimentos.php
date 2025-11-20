<?php

namespace App\Services\ResultadoPeriodo\Actions;

use App\Enum\StatusDiversosEnum;
use App\Models;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ImportarAbastecimentos
{
    protected Models\ResultadoPeriodo $resultadoPeriodo;
    protected Collection $abastecimentos;

    public function __construct(int $resultadoPeriodoId, protected bool $considerarPeriodo = true)
    {
        $this->resultadoPeriodo = Models\ResultadoPeriodo::findOrFail($resultadoPeriodoId);
    }

    public function handle()
    {
        if ($this->resultadoPeriodo->status !== StatusDiversosEnum::PENDENTE) {
            Log::info('Resultado período não está em status PENDENTE. Importação de abastecimentos ignorada.', [
                'metodo' => __METHOD__,
                'resultado_periodo_id' => $this->resultadoPeriodo->id,
                'status_atual' => $this->resultadoPeriodo->status,
            ]);
            return;
        }

        $this->getAbastecimentosSemVinculo();

        if ($this->abastecimentos->isEmpty()) {
            Log::info('Nenhum abastecimento para importar encontrado.', [
                'metodo' => __METHOD__,
                'veiculo_id' => $this->resultadoPeriodo->veiculo_id,
                'data_inicial' => $this->resultadoPeriodo->data_inicial,
            ]);
        }

        $this->vincularAbastecimentos();
    }

    private function getAbastecimentosSemVinculo(): void
    {
        $query = Models\Abastecimento::query()
            ->select('id')
            ->where('veiculo_id', $this->resultadoPeriodo->veiculo_id)
            ->whereNull('resultado_periodo_id');

        if ($this->considerarPeriodo) {
            $query->whereBetween('data_abastecimento', [
                $this->resultadoPeriodo->data_inicial,
                $this->resultadoPeriodo->data_final,
            ]);
        }

        $this->abastecimentos = $query->get();
    }

    private function vincularAbastecimentos(): void
    {
        $abastecimentoIds = $this->abastecimentos->pluck('id')->toArray();

        if (empty($abastecimentoIds)) {
            return;
        }

        Models\Abastecimento::query()
            ->whereIn('id', $abastecimentoIds)
            ->update([
                'resultado_periodo_id' => $this->resultadoPeriodo->id,
                'updated_at' => now(),
            ]);
    }
}
