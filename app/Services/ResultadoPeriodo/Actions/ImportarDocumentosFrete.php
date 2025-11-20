<?php

namespace App\Services\ResultadoPeriodo\Actions;

use App\Enum\StatusDiversosEnum;
use App\Models;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ImportarDocumentosFrete
{
    protected Models\ResultadoPeriodo $resultadoPeriodo;
    protected Collection $documentosFrete;

    public function __construct(int $resultadoPeriodoId, protected bool $considerarPeriodo = true)
    {
        $this->resultadoPeriodo = Models\ResultadoPeriodo::findOrFail($resultadoPeriodoId);
    }

    public function handle()
    {
        if ($this->resultadoPeriodo->status !== StatusDiversosEnum::PENDENTE->value) {
            Log::info('Resultado período não está em status PENDENTE. Importação de documentos de frete ignorada.', [
                'metodo' => __METHOD__,
                'resultado_periodo_id' => $this->resultadoPeriodo->id,
                'status_atual' => $this->resultadoPeriodo->status,
            ]);
            return;
        }

        $this->getDocumentosFreteSemVinculo();

        if ($this->documentosFrete->isEmpty()) {
            Log::info('Nenhum documento de frete para importar encontrado.', [
                'metodo' => __METHOD__,
                'veiculo_id' => $this->resultadoPeriodo->veiculo_id,
                'data_inicial' => $this->resultadoPeriodo->data_inicial,
            ]);
        }

        $this->vincularDocumentosFrete();
    }

    private function getDocumentosFreteSemVinculo(): void
    {
        $query = Models\DocumentoFrete::query()
            ->select('id')
            ->where('veiculo_id', $this->resultadoPeriodo->veiculo_id)
            ->whereNull('resultado_periodo_id');

        if ($this->considerarPeriodo) {
            $query->whereBetween('data_emissao', [
                $this->resultadoPeriodo->data_inicial,
                $this->resultadoPeriodo->data_final,
            ]);
        }

        $this->documentosFrete = $query->get();
    }

    private function vincularDocumentosFrete(): void
    {
        $documentoFreteIds = $this->documentosFrete->pluck('id')->toArray();

        if (empty($documentoFreteIds)) {
            return;
        }

        Models\DocumentoFrete::query()
            ->whereIn('id', $documentoFreteIds)
            ->update([
                'resultado_periodo_id' => $this->resultadoPeriodo->id,
                'updated_at' => now(),
            ]);
    }
}
