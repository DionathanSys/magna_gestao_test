<?php

namespace App\Imports;

use App\Models;
use App\Services;
use App\Contracts\XlsxImportInterface;
use App\Enum\Frete\TipoDocumentoEnum;
use Illuminate\Support\Facades\Log;

class ViagemSoftLog extends BaseXlsxImport
{
    public static function columns(): array
    {
        return [
            'Viagem',
            'Carga Cliente',
            'Destino',
            'Inicio',
            'Fim',
            'Placa',
            'Condutor Viagem',
            'Km Rodado',
            'Km Sugerida',
        ];
    }

    public static function columnMap(): array
    {
        return [
            'Viagem' => [
                'column' => 'numero_viagem',
                'type' => 'string',
                'max_length' => 50,
            ],
            'Carga Cliente' => [
                'column' => 'documento_transporte',
                'type' => 'string',
                'max_length' => 50,
                'nullable' => true,
            ],
            'Destino' => [
                'column' => 'integrado',
                'type' => 'string',
                'nullable' => true,
            ],
            'Inicio' => [
                'column' => 'data_inicio',
                'type' => 'date',
                'format' => 'Y-m-d H:i',
            ],
            'Fim' => [
                'column' => 'data_fim',
                'type' => 'date',
                'format' => 'Y-m-d H:i',
            ],
            'Placa' => [
                'column' => 'veiculo_id',
                'type' => 'relationship',
                'model' => Models\Veiculo::class,
                'search_column' => 'placa',
            ],
            'Condutor Viagem' => [
                'column' => 'motorista',
                'type' => 'string',
                'nullable' => true,
            ],
            'Km Rodado' => [
                'column' => 'km_rodado',
                'type' => 'decimal',
                'nullable' => true,
            ],
            'Km Sugerida' => [
                'column' => 'km_pago',
                'type' => 'decimal',
                'nullable' => true,
            ],
        ];
    }

    public function processRow(array $row): void
    {
        // Validações específicas de cada linha
        $dadosConvertidos = $this->mapRowData($row);
        Log::debug('Dados convertidos', [
            'dados' => $dadosConvertidos,
        ]);

        $observacao = $dadosConvertidos['observacao'] ?? '';
        unset($dadosConvertidos['observacao']);

        $dadosConvertidos['documento_transporte'] = $this->extrairNumeroDocumentoTransporte($observacao);
        $dadosConvertidos['tipo_documento'] = TipoDocumentoEnum::CTE;

        // Salva no banco de dados
        $service = new Services\DocumentoFrete\DocumentoFreteService();
        $service->criarDocumentoFrete($dadosConvertidos);
    }

    protected function processRelationship($valor, array $config)
    {
        // Se for a coluna de placa, remove os colchetes
        if ($config['search_column'] === 'placa') {
            $valor = trim($valor, '[]');
        }

        // Chama o método pai para continuar o processamento normal
        return parent::processRelationship($valor, $config);
    }

    private function extrairNumeroDocumentoTransporte(string $valor): int
    {
        return preg_match('/Transporte:\s*(\d+)/', $valor, $matches) ? $matches[1] : null;
    }

}
