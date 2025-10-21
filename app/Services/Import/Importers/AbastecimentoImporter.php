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
        private Services\Veiculo\VeiculoService     $veiculoService

    ) {}

    public function getRequiredColumns(): array
    {
        return [
            'Cód. Prd',
            'Abastecimento',
            'Forn. Abastecimento',
            'Dt Abastecimento',
            'Placa',
            'Km',
            'Qtd Litros',
            'Vlr. Unitário',
            'Vlr. Total',
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
            'Cód. Prd'              => 'required|string',
            'Abastecimento'         => 'required|string',
            'Forn. Abastecimento'   => 'required|string',
            'Dt Abastecimento'      => 'required|date_format:d/m/Y H:i:s',
            'Placa'                 => 'required|string',
            'Km'                    => 'required|numeric|min:0',
            'Qtd Litros'            => 'required|numeric|min:0',
            'Vlr. Unitário'         => 'required|numeric|min:0',
            'Vlr. Total'            => 'required|numeric|min:0',
        ], [
            'Cód. Prd.required'             => "A coluna 'Cód. Prd' é obrigatória na linha {$rowNumber}.",
            'Abastecimento.required'        => "A coluna 'Abastecimento' é obrigatória na linha {$rowNumber}.",
            'Forn. Abastecimento.required'  => "A coluna 'Forn. Abastecimento' é obrigatória na linha {$rowNumber}.",
            'Dt Abastecimento.required'     => "A coluna 'Dt Abastecimento' é obrigatória na linha {$rowNumber}.",
            'Dt Abastecimento.date_format'  => "A coluna 'Dt Abastecimento' deve estar no formato 'd/m/Y H:i:s' na linha {$rowNumber}.",
            'Placa.required'                => "A coluna 'Placa' é obrigatória na linha {$rowNumber}.",
            'Km.required'                   => "A coluna 'Km' é obrigatória na linha {$rowNumber}.",
            'Qtd Litros.required'           => "A coluna 'Qtd Litros' é obrigatória na linha {$rowNumber}.",
            'Vlr. Unitário.required'        => "A coluna 'Vlr. Unitário' é obrigatória na linha {$rowNumber}.",
            'Vlr. Total.required'           => "A coluna 'Vlr. Total' é obrigatória na linha {$rowNumber}.",
        ]);

        dd($validator->errors()->all(), $row, $rowNumber);

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

        return $errors;
    }

    public function transform(array $row): array
    {
        try {

            $veiculo_id = $this->veiculoService->getVeiculoIdByPlaca($row['Placa']);
            $tipo_combustivel = Enum\Abastecimento\TipoCombustivelEnum::fromProductCode($row['Cód. Prd']);

        } catch (\Exception $e) {
            Log::error("Erro ao transformar dados", [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'error' => $e->getMessage(),
                'data' => $row
            ]);
        }

        $km_pago = (float) str_replace(',', '.', $row['Km Sugerida']);

        return [
            'veiculo_id'            => $veiculo_id,
            'id_abastecimento'      => $row['Abastecimento'],
            'quilometragem'         => $row['Km'] ?? 0,
            'posto_combustivel'     => Str::upper($row['Forn. Abastecimento']),
            'tipo_combustivel'      => $tipo_combustivel?->value,
            'data_abastecimento'    => Carbon::createFromFormat('d/m/Y H:i:s', $row['Dt Abastecimento'])->toDateTimeString(),
            'quantidade'            => (float) str_replace(',', '.', $row['Qtd Litros']),
            'preco_por_litro'       => (float) str_replace(',', '.', $row['Vlr. Unitário']),

        ];
    }

    public function process(array $data, int $rowNumber): ?Models\Viagem
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

        return $abastecimento;
    }
}
