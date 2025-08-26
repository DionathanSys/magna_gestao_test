<?php

namespace App\Imports;

use App\Models;
use App\Services;
use App\Contracts\XlsxImportInterface;
use App\Enum\Frete\TipoDocumentoEnum;
use Illuminate\Support\Facades\Log;

class DocumentoFreteImport extends BaseXlsxImport
{
    public static function columns(): array
    {
        return [
            'Marca [Placa] (Veículos)',
            'Nome Parceiro (Parceiro)',
            'Nro. Nota',
            'Dt. Neg.',
            'Vlr. Nota',
            'Vlr. do ICMS',
            'Observação',
        ];
    }

    public static function columnMap(): array
    {
        return [
            'Marca [Placa] (Veículos)' => [
                'column' => 'veiculo_id',
                'type' => 'relationship',
                'model' => Models\Veiculo::class,
                'search_column' => 'placa',

            ],
            'Nro. Nota' => [
                'column' => 'numero_documento',
                'type' => 'string',
            ],
            'Nome Parceiro (Parceiro)' => [
                'column' => 'parceiro',
                'type' => 'string',
            ],
            'Dt. Neg.' => [
                'column' => 'data_emissao',
                'type' => 'date',
                'format' => 'd/m/Y',
            ],
            'Vlr. Nota' => [
                'column' => 'valor_total',
                'type' => 'decimal',
                'decimals' => 2,
            ],
            'Vlr. do ICMS' => [
                'column' => 'valor_icms',
                'type' => 'decimal',
                'decimals' => 2,
            ],
            'Observação' => [
                'column' => 'observacao',
                'type' => 'string',
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

    private function extrairNumeroDocumentoTransporte(string $valor): string
    {
        return preg_match('/Transporte:\s*(\d+)/', $valor, $matches) ? $matches[1] : null;
    }

}
