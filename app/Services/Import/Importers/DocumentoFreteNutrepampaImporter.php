<?php

namespace App\Services\Import\Importers;

use App\Models;
use App\Enum;
use App\Contracts\ExcelImportInterface;
use App\Enum\Frete\TipoDocumentoEnum;
use App\Services;
use App\Traits\FormatarValorTrait;
use App\Traits\ServiceResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentoFreteNutrepampaImporter implements ExcelImportInterface
{
    use ServiceResponseTrait;
    use FormatarValorTrait;

    public function __construct(
        private Services\Veiculo\VeiculoService                 $veiculoService,
        private Services\DocumentoFrete\DocumentoFreteService   $documentoService
    ) {}

    public function getRequiredColumns(): array
    {
        return [
            'Nronico',  //Nro. Único
            'VlrNota',
            'NroNota',
            'DtNeg',
            'VlrdoICMS',
            'Placa',
            'NomeParceiroParceiro', //Nome Parceiro Origem
            'NomeParceiroParcDestinatrio', //Nome Parceiro (ParcDestinatário)
            'Observao', //Observação
        ];
    }

    public function getOptionalColumns(): array
    {
        return [];
    }

    public function validate(array $row, int $rowNumber): array
    {
        Log::debug(__METHOD__ . '@' . __LINE__);

        $errors = [];

        $this->normalizarValoresMonetarios($row, ['VlrNota', 'VlrdoICMS']);

        // Validação básica
        $validator = Validator::make($row, [
            'Nronico'                        => 'required|numeric',
            'VlrNota'                        => 'required|decimal:2',
            'NroNota'                        => 'required|numeric',
            'VlrdoICMS'                      => 'required|decimal:2',
            'DtNeg'                          => 'required|date',
            'Placa'                          => 'required|string|exists:veiculos,placa',
            'NomeParceiroParceiro'           => 'required|string',
            'NomeParceiroParcDestinatrio'    => 'required|string',
        ], [
            'Nronico.required'                      => 'O campo Nro. Único é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Nronico.numeric'                       => 'O campo Nro. Único deve ser um número válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Placa.required'                        => 'A Placa é obrigatória ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Placa.string'                          => 'A Placa deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Placa.exists'                          => 'A Placa informada não existe na base de dados ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NomeParceiroParceiro.required'         => 'O Nome do Parceiro é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NomeParceiroParceiro.string'           => 'O Nome do Parceiro deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NomeParceiroParcDestinatrio.required'  => 'O Nome do Destinatário é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NomeParceiroParcDestinatrio.string'    => 'O Nome do Destinatário deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'VlrNota.required'                      => 'O Valor da Nota é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'VlrNota.decimal'                       => 'O Valor da Nota deve ser numérico ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'VlrNota.min'                           => 'O Valor da Nota deve ser maior ou igual a zero ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'VlrdoICMS.decimal'                     => 'O Valor do ICMS deve ser numérico ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NroNota.required'                      => 'O Número da Nota é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NroNota.numeric'                       => 'O Número da Nota deve ser numérico ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'DtNeg.required'                        => 'A Data de Negociação é obrigatória ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'DtNeg.date_format'                     => 'A Data de Negociação deve estar no formato dd/mm/aaaa ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
        ]);

        if ($validator->fails()) {
            $errors = array_merge($errors, $validator->errors()->all());
            Log::error('Validação falhou na importação de documento de frete', [
                'metodo'    => __METHOD__ . '@' . __LINE__,
                'row'       => $rowNumber,
                'data'      => $row,
                'errors'    => $errors
            ]);
        }

        return $errors;
    }

    public function transform(array $row): array
    {
        Log::debug(__METHOD__ . '@' . __LINE__);

        $veiculo    = $this->veiculoService->getVeiculoByPlaca($row['Placa']);
        $veiculo_id = $veiculo->id;

        $valorTotal = $this->converterParaFloat($row['VlrNota']);
        $valorICMS  = $this->converterParaFloat($row['VlrdoICMS'] ?? null);

        if (str_contains(Str::lower($row['Observao'] ?? ''), 'complemento')) {
            $tipoDocumento = TipoDocumentoEnum::CTE_COMPLEMENTO;
        }

        return [
            'veiculo_id'            => $veiculo_id,
            'parceiro_origem'       => $row['NomeParceiroParceiro'],
            'parceiro_destino'      => $row['NomeParceiroParcDestinatrio'],
            'numero_documento'      => $row['NroNota'],
            'documento_transporte'  => $row['Nronico'],
            'tipo_documento'        => $tipoDocumento ?? TipoDocumentoEnum::CTE,
            'data_emissao'          => $row['DtNeg'],
            'valor_total'           => $valorTotal,
            'valor_icms'            => isset($row['VlrdoICMS']) ? $valorICMS : 0.0,
        ];
    }

    private function normalizarDataCampo(array &$row, string $campo): void
    {
        if (empty($row[$campo])) {
            return;
        }

        $value = trim($row[$campo]);

        // Prioridade: formatos mês/dia/ano (m/d/Y) com/sem zeros
        $formats = [
            'n/j/Y', // 1/3/2025
            'n/d/Y', // 1/03/2025
            'm/j/Y', // 01/3/2025
            'm/d/Y', // 01/03/2025
        ];

        $parsed = false;
        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $value);
                // Se conseguiu parsear, normaliza para YYYY-MM-DD (o formato esperado nas regras/transform)
                $row[$campo] = $dt->format('Y-m-d');
                Log::debug('Data normalizada para o campo ' . $campo, [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'original' => $value,
                    'normalizada' => $row[$campo],
                ]);
                $parsed = true;
                break;
            } catch (\Exception $e) {
                // tenta próximo formato
            }
        }

        if (! $parsed) {
            Log::warning('Não foi possível normalizar a data para o campo ' . $campo, [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'value' => $value,
            ]);
        }
    }

    public function process(array $data, int $rowNumber): ?Models\Viagem
    {
        Log::debug('Processando linha de importação de documento frete Nutrepampa - Linha: ' . $rowNumber, [
            'metodo'    => __METHOD__ . '@' . __LINE__,
            'row'       => $rowNumber,
            'data'      => $data,
        ]);

        $this->normalizarDataCampo($data, 'DtNeg');

        $errors = $this->validate($data, $rowNumber);

        if (!empty($errors)) {
            Log::error('Erros de validação na importação de documento frete', [
                'metodo'    => __METHOD__ . '@' . __LINE__,
                'row'       => $rowNumber,
                'data'      => $data,
                'errors'    => $errors
            ]);
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);
            return null;
        }

        $transformedData = $this->transform($data);

        $viagem = $this->documentoService->criarDocumentoFrete($transformedData);

        if ($this->documentoService->hasError()) {
            Log::error('Erro ao importar documento frete', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'data' => $transformedData,
                'errors' => $this->documentoService->getErrors()
            ]);
            $this->setError("Erro na linha {$rowNumber}.", $this->documentoService->getErrors());
            return null;
        }

        return $viagem;
    }
}
