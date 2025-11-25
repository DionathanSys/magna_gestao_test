<?php

namespace App\Services\Import\Importers;

use App\Models;
use App\Enum;
use App\Contracts\ExcelImportInterface;
use App\Enum\Frete\TipoDocumentoEnum;
use App\Services;
use App\Traits\ServiceResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentoFreteNutrepampaImporter implements ExcelImportInterface
{
    use ServiceResponseTrait;

    public function __construct(
        private Services\Veiculo\VeiculoService                 $veiculoService,
        private Services\DocumentoFrete\DocumentoFreteService   $documentoService
    ) {}

    public function getRequiredColumns(): array
    {
        return [
            'NroUnico',
            'VlrNota',
            'NroNota',
            'DtNeg',
            'VlrdoICMS',
            'Placa',
            'NomeParceiro(Parceiro)',
            'NomeParceiro(ParcDestinatário)',
        ];
    }

    public function getOptionalColumns(): array
    {
        return [];
    }

    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        // Validação básica
        $validator = Validator::make($row, [
            'NroUnico'                       => 'required|numeric',
            'VlrNota'                        => 'required|numeric|min:0',
            'NroNota'                        => 'required|numeric',
            'VlrdoICMS'                      => 'nullable|numeric',
            'DtNeg'                          => 'required|date_format:d/m/Y',
            'Placa'                          => 'required|string|exists:veiculos,placa',
            'NomeParceiro(Parceiro)'         => 'required|string',
            'NomeParceiro(ParcDestinatário)' => 'required|string',
        ], [
            'NroUnico.required'         => 'O campo Nro. Único é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NroUnico.numeric'          => 'O campo Nro. Único deve ser um número válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Placa.required'            => 'A Placa é obrigatória ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Placa.string'              => 'A Placa deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Placa.exists'              => 'A Placa informada não existe na base de dados ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NomeParceiro(Parceiro).required'          => 'O Nome do Parceiro é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),   
            'NomeParceiro(Parceiro).string'            => 'O Nome do Parceiro deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NomeParceiro(ParcDestinatário).required'  => 'O Nome do Destinatário é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),   
            'NomeParceiro(ParcDestinatário).string'              => 'O Nome do Destinatário deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'VlrNota.required'          => 'O Valor da Nota é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'VlrNota.numeric'           => 'O Valor da Nota deve ser numérico ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'VlrNota.min'               => 'O Valor da Nota deve ser maior ou igual a zero ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'VlrdoICMS.numeric'        => 'O Valor do ICMS deve ser numérico ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NroNota.required'          => 'O Número da Nota é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'NroNota.numeric'           => 'O Número da Nota deve ser numérico ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'DtNeg.required'            => 'A Data de Negociação é obrigatória ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'DtNeg.date_format'         => 'A Data de Negociação deve estar no formato dd/mm/aaaa ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
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

        return [
            'veiculo_id'            => $veiculo_id,
            'parceiro_origem'       => $row['NomeParceiro(Parceiro)'],
            'parceiro_destino'      => $row['NomeParceiro(ParcDestinatário)'],
            'numero_documento'      => $row['NroNota'],
            'documento_transporte'  => $row['NroUnico'],
            'tipo_documento'        => TipoDocumentoEnum::CTE,
            'data_emissao'          => Carbon::createFromFormat('d/m/Y', $row['DtNeg'])->format('Y-m-d'),
            'valor_total'           => (float) $row['VlrNota'],
            'valor_icms'            => isset($row['VlrdoICMS']) ? (float) $row['VlrdoICMS'] : 0.0,
        ];
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
