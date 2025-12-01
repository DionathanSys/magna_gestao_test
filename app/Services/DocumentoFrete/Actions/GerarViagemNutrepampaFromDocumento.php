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

        //TODO usar valores de R$/km para identifcar km pago
    }

    private function processarDocumentosFrete(): void
    {
        $documentosGroupVeiculoId = $this->documentosFrete->groupBy(['veiculo_id', function (DocumentoFrete $documento) {
            return $documento->data_emissao;
        }]);

        $documentosGroupVeiculoId->each(function (Collection $documentosGroupDataEmissao, $veiculoId) {

            $documentosGroupDataEmissao->each(function (Collection $documentosDataEmissao, $dataEmissao) use ($veiculoId) {

                $documentosDataEmissao->groupBy('valor_total')->each(function (Collection $documentosGroupValorTotal, $valorTotal) use ($veiculoId, $dataEmissao) {

                    $documentos = $documentosGroupValorTotal->toArray();

                    $this->data[] = [
                        'veiculo_id' => $veiculoId,
                        'data_emissao' => $dataEmissao,
                        'valor_total' => $valorTotal,
                        'documentos_frete_ids' => $documentos,
                    ];

                });
               
            });

        });

        Log::debug('Dados agrupados para criação de viagens Nutrepampa a partir dos documentos de frete.', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'data' => $this->data,
        ]);

    }

   

}