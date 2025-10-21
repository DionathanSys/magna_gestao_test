<?php

namespace App\Services\Import\Importers;

use App\Models;
use App\Enum;
use App\Contracts\ExcelImportInterface;
use App\Services;
use App\Traits\ServiceResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AbastecimentoImporter implements ExcelImportInterface
{
    use ServiceResponseTrait;

    public function __construct(
        private Services\Abastecimento\AbastecimentoService $abastecimentoService,
        private Services\Veiculo\VeiculoService             $veiculoService

    ) {}

    public function getRequiredColumns(): array
    {
        return [
            'CdPrd',
            'Abastecimento',
            'FornAbastecimento',
            'DtAbastecimento',
            'Placa',
            'Km',
            'QtdLitros',
            'VlrUnitrio',
            'VlrTotal',
        ];
    }

    public function getOptionalColumns(): array
    {
        return [];
    }

    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        $validator = Validator::make($row, [
            'CdPrd'                 => 'required',
            'Abastecimento'         => 'required',
            'FornAbastecimento'     => 'required',
            'DtAbastecimento'       => 'required|date_format:d/m/Y H:i:s',
            'Placa'                 => 'required|string',
            'Km'                    => 'required|numeric|min:0',
            'QtdLitros'             => 'required|numeric|min:0',
            'VlrUnitrio'           => 'required|numeric|min:0',
            'VlrTotal'              => 'required|numeric|min:0',
        ], [
            'CdPrd.required'                => "A coluna 'Cód Prd' é obrigatória na linha {$rowNumber}.",
            'Abastecimento.required'        => "A coluna 'Abastecimento' é obrigatória na linha {$rowNumber}.",
            'FornAbastecimento.required'    => "A coluna 'Forn. Abastecimento' é obrigatória na linha {$rowNumber}.",
            'DtAbastecimento.required'      => "A coluna 'Dt. Abastecimento' é obrigatória na linha {$rowNumber}.",
            'DtAbastecimento.date_format'   => "A coluna 'Dt. Abastecimento' deve estar no formato 'd/m/Y H:i:s' na linha {$rowNumber}.",
            'Placa.required'                => "A coluna 'Placa' é obrigatória na linha {$rowNumber}.",
            'Km.required'                   => "A coluna 'Km' é obrigatória na linha {$rowNumber}.",
            'QtdLitros.required'            => "A coluna 'Qtd Litros' é obrigatória na linha {$rowNumber}.",
            'VlrUnitrio.required'          => "A coluna 'Vlr Unitário' é obrigatória na linha {$rowNumber}.",
            'VlrTotal.required'             => "A coluna 'Vlr Total' é obrigatória na linha {$rowNumber}.",
        ]);

        if ($validator->fails()) {
            $errors = array_merge($errors, $validator->errors()->all());
        }

        // Validações específicas de negócio
        if (!empty($row['Placa'])) {
            $veiculo = Models\Veiculo::where('placa', $row['Placa'])->first();
            if (!$veiculo) {
                $errors[] = "Veículo com placa '{$row['Placa']}' não encontrado.";
            }
        }

        Log::debug('Validação de linha de importação de abastecimento', [
            'rowNumber' => $rowNumber,
            'data' => $row,
        ]);

        return $errors;
    }

    public function transform(array $row): array
    {
        try {

            $veiculo_id = $this->veiculoService->getVeiculoIdByPlaca($row['Placa']);
            $tipo_combustivel = Enum\Abastecimento\TipoCombustivelEnum::fromProductCode($row['CdPrd']);

        } catch (\Exception $e) {
            Log::error("Erro ao transformar dados", [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'error' => $e->getMessage(),
                'data' => $row
            ]);
        }

        Log::debug('Transformação de linha de importação de abastecimento', [
            'data' => $row,
            'veiculo_id' => $veiculo_id ?? null,
            'tipo_combustivel' => $tipo_combustivel?->value ?? null,
        ]);

        return [
            'veiculo_id'            => $veiculo_id,
            'id_abastecimento'      => $row['Abastecimento'],
            'quilometragem'         => $row['Km'] ?? 0,
            'posto_combustivel'     => Str::upper($row['FornAbastecimento']),
            'tipo_combustivel'      => $tipo_combustivel?->value,
            'data_abastecimento'    => Carbon::createFromFormat('d/m/Y H:i:s', $row['DtAbastecimento'])->toDateTimeString(),
            'quantidade'            => (float) str_replace(',', '.', $row['QtdLitros']),
            'preco_por_litro'       => (float) str_replace(',', '.', $row['VlrUnitrio']),

        ];
    }

    public function process(array $data, int $rowNumber): ?Models\Abastecimento
    {
        $errors = $this->validate($data, $rowNumber);

        if (!empty($errors)) {
            Log::error('Erro durante validação na importação de abastecimento', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'rowNumber' => $rowNumber,
                'data'   => $data,
                'errors' => $errors
            ]);
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);
            return null;
        }

        $transformedData = $this->transform($data);

        $abastecimento = $this->abastecimentoService->criar($transformedData);

        if ($this->abastecimentoService->hasError()) {
            Log::error('Erro ao importar abastecimento', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'data' => $transformedData,
                'errors' => $this->abastecimentoService->getErrors()
            ]);
            $this->setError("Erro na linha {$rowNumber}.", $this->abastecimentoService->getErrors());
            return null;
        }

        Log::debug('Processamento de linha de importação de abastecimento concluído com sucesso', [
            'rowNumber' => $rowNumber,
            'abastecimento_id' => $abastecimento->id,
        ]);

        return $abastecimento;
    }
}
