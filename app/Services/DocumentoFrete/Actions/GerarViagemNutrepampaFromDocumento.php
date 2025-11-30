<?php

namespace App\Services\DocumentoFrete\Actions;

use App\Models\DocumentoFrete;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class GerarViagemNutrepampaFromDocumento
{
    protected array $data = [];
    protected array $errors = [];
    protected array $viagensCriadas = [];

    public function __construct(
        protected Collection $documentosFrete
    ) {}

    public function handle()
    {
        $this->processarDocumentosFrete();
    }

    private function processarDocumentosFrete(): void
    {
        $documentosGroupVeiculoId = $this->documentosFrete->groupBy(['veiculo_id', function (DocumentoFrete $documento) {
            return $documento->data_emissao;
        }]);

        $documentosGroupVeiculoId->each(function (Collection $documentosGroupDataEmissao, $veiculoId) {

            $documentosGroupDataEmissao->each(function (Collection $documentosDataEmissao, $dataEmissao) use ($veiculoId) {

                $documentos = $this->separarEmSequencias($documentosDataEmissao);

                $this->data[$veiculoId . '.' . $dataEmissao] = [
                    'veiculo_id' => $veiculoId,
                    'data_emissao' => $dataEmissao,
                    'documentos_frete' => $documentos,
                ];
            });

        });

        Log::debug('Dados agrupados para criação de viagens Nutrepampa a partir dos documentos de frete.', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'data' => $this->data,
        ]);

    }

    private function separarEmSequencias(Collection $documentos): array
    {

        Log::debug('Separando documentos em sequências.', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'documentos_ids' => $documentos->pluck('numero_documento', 'id')->toArray(),
        ]);

        if ($documentos->isEmpty()) {
            return [];
        }

        $sequencias = [];
        $sequenciaAtual = [];

        foreach ($documentos as $index => $doc) {
            if (empty($sequenciaAtual)) {
                // Primeira documento da sequência
                $sequenciaAtual[] = $doc;
                continue;
            }

            $ultimoDoc = end($sequenciaAtual);
            $numeroAtual = (int) $doc->numero_documento;
            $numeroAnterior = (int) $ultimoDoc->numero_documento;

            // Verificar se é sequencial (diferença de 1)
            if ($numeroAtual === $numeroAnterior + 1) {
                // Continua a sequência
                $sequenciaAtual[] = $doc;
            } else {
                // Quebra a sequência
                $sequencias[] = $sequenciaAtual;
                $sequenciaAtual = [$doc];
            }
        }

        // Adicionar última sequência
        if (!empty($sequenciaAtual)) {
            $sequencias[] = $sequenciaAtual;
        }

        return $sequencias;
    }

}