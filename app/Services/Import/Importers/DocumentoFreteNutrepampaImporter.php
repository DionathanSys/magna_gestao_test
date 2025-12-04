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
        $errors = [];

        $this->normalizarValoresMonetarios($row, ['VlrNota', 'VlrdoICMS']);

        // Validação básica
        $validator = Validator::make($row, [
            'Nronico'                        => 'required|numeric',
            'VlrNota'                        => 'required|decimal:2',
            'NroNota'                        => 'required|numeric',
            'VlrdoICMS'                      => 'required|decimal:2',
            'DtNeg'                          => 'required|date_format:m/d/Y',
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
        $veiculo    = $this->veiculoService->getVeiculoByPlaca($row['Placa']);
        $veiculo_id = $veiculo->id;

        $valorTotal = $this->converterParaFloat($row['VlrNota']);
        $valorICMS = $this->converterParaFloat($row['VlrdoICMS'] ?? null);

        Log::debug('Transformando dados para importação de documento frete Nutrepampa', [
            'metodo'    => __METHOD__ . '@' . __LINE__,
            'row'       => $row,
            'obs'       => str_contains(Str::lower($row['Observao'] ?? ''), 'complemento')
        ]);

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
            'data_emissao'          => Carbon::createFromFormat('m/d/Y', $row['DtNeg'])->format('Y-m-d'),
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
        $formats = [
            'd/m/Y', // 03/11/2025
            'j/n/Y', // 3/11/2025 ou 11/3/2025
            'd/n/Y', // 03/11/2025 with month single-digit
            'j/m/Y', // day single-digit, month two-digit
            'm/d/Y', // 11/03/2025 (US)
            'n/j/Y', // 1/3/2025 (US without leading zeros)
            'Y-m-d', // already normalized
        ];

        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $value);
                // normaliza para dd/mm/YYYY
                $row[$campo] = $dt->format('d/m/Y');
                return;
            } catch (\Exception $e) {
                Log::warning('Falha ao normalizar data para o campo ' . $campo, [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'value' => $value,
                    'format_attempted' => $fmt,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    public function process(array $data, int $rowNumber): ?Models\Viagem
    {
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
