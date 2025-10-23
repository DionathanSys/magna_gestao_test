<?php

namespace App\Services\Import\Importers;

use App\{Models, Enum, Services, Rules};
use App\Contracts\ExcelImportInterface;
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
            'Placa'                 => ['required', 'string', 'exists:veiculos,placa'],
            'Km'                    => 'required|numeric|min:0',
            'QtdLitros'             => 'required|numeric|min:0',
            'VlrUnitrio'            => 'required|numeric|min:0',
            'VlrTotal'              => 'required|numeric|min:0',
        ], [
            'CdPrd.required'                => "A coluna 'Cód Prd' é obrigatória na linha {$rowNumber}.",
            'Abastecimento.required'        => "A coluna 'Abastecimento' é obrigatória na linha {$rowNumber}.",
            'FornAbastecimento.required'    => "A coluna 'Forn. Abastecimento' é obrigatória na linha {$rowNumber}.",
            'DtAbastecimento.required'      => "A coluna 'Dt. Abastecimento' é obrigatória na linha {$rowNumber}.",
            'DtAbastecimento.date_format'   => "A coluna 'Dt. Abastecimento' deve estar no formato 'd/m/Y H:i:s' na linha {$rowNumber}.",
            'Placa.required'                => "A coluna 'Placa' é obrigatória na linha {$rowNumber}.",
            'Placa.string'                  => "A coluna 'Placa' deve ser uma string na linha {$rowNumber}.",
            'Placa.exists'                  => "O veículo com a placa '{$row['Placa']}' não foi encontrado na linha {$rowNumber}.",
            'Km.required'                   => "A coluna 'Km' é obrigatória na linha {$rowNumber}.",
            'QtdLitros.required'            => "A coluna 'Qtd Litros' é obrigatória na linha {$rowNumber}.",
            'VlrUnitrio.required'           => "A coluna 'Vlr Unitário' é obrigatória na linha {$rowNumber}.",
            'VlrTotal.required'             => "A coluna 'Vlr Total' é obrigatória na linha {$rowNumber}.",
            'VlrTotal.numeric'              => "A coluna 'Vlr Total' deve ser numérica na linha {$rowNumber}.",
        ]);

        if ($validator->fails()) {
            $errors = array_merge($errors, $validator->errors()->all());
        }

        if (!empty($errors)) {
            Log::info('Validado dados extraídos - Abastecimento ID: ' . ($row['id_abastecimento'] ?? 'N/A'), [
                'metodo'    => __METHOD__ . '@' . __LINE__,
                'rowNumber' => $rowNumber,
                'data'      => $row,
            ]);
        }

        return $errors;
    }

    public function transform(array $row): array
    {
        try {
            $veiculo_id = $this->veiculoService->getVeiculoIdByPlaca($row['Placa']);
            $tipo_combustivel = Enum\Abastecimento\TipoCombustivelEnum::fromProductCode($row['CdPrd']);
            $precoTotal = (float) str_replace(',', '', $row['VlrTotal']);
            $quantidade = (float) str_replace(',', '.', $row['QtdLitros']);
            $dataAbastecimento = Carbon::createFromFormat('d/m/Y H:i:s', $row['DtAbastecimento'])->toDateTimeString();

            //TODO: Utilizar quantidade de casas decimais através de configuração
            //Ajusta o moneycast tb
            $precoLitroCalculado = $quantidade > 0 ? round($precoTotal / $quantidade, 4) : 0;

            Log::debug('Transformação da linha de importação de abastecimento', [
                'data'              => $row,
                'precoTotal'        => $precoTotal,
                'quantidade'        => $quantidade,
                'preco_por_litro_calculado' => $precoLitroCalculado,
            ]);

        } catch (\Exception $e) {
            Log::error(__METHOD__ . '@' . __LINE__, [
                'error' => $e->getMessage(),
                'data'  => $row
            ]);
        }

        $data = [
            'veiculo_id'            => $veiculo_id,
            'id_abastecimento'      => $row['Abastecimento'],
            'quilometragem'         => $row['Km'] ?? 0,
            'posto_combustivel'     => Str::upper($row['FornAbastecimento']),
            'tipo_combustivel'      => $tipo_combustivel?->value,
            'data_abastecimento'    => $dataAbastecimento,
            'quantidade'            => $quantidade,
            'preco_por_litro'       => $precoLitroCalculado,
        ];

        Log::info('Ajuste de dados extraídos - Abastecimento ID: ' . ($row['id_abastecimento'] ?? 'N/A'), [
            'metodo'            => __METHOD__ . '@' . __LINE__,
            'dadosRecebidos'    => $row,
            'dadosAjustados'    => $data,
            'id_abastecimento'  => $row['Abastecimento'],
            'veiculo_id'        => $veiculo_id ?? null,
            'tipo_combustivel'  => $tipo_combustivel?->value ?? null,
        ]);

        return $data;
    }

    public function process(array $data, int $rowNumber): ?Models\Abastecimento
    {
        $errors = $this->validate($data, $rowNumber);

        if (!empty($errors)) {
            Log::error(__METHOD__ . '@' . __LINE__, [
                'rowNumber' => $rowNumber,
                'data'      => $data,
                'errors'    => $errors
            ]);
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);
            return null;
        }

        $transformedData = $this->transform($data);

        $abastecimento = $this->abastecimentoService->criar($transformedData);

        if ($this->abastecimentoService->hasError()) {
            Log::error(__METHOD__ . '@' . __LINE__, [
                'data'      => $transformedData,
                'errors'    => $this->abastecimentoService->getErrors()
            ]);
            $this->setError("Erro na linha {$rowNumber}.", $this->abastecimentoService->getErrors());
            return null;
        }

        Log::info('Processado abastecimento ID: ' . $abastecimento->id, [
            'rowNumber'         => $rowNumber,
            'data'              => $data,
            'abastecimento_id'  => $abastecimento->id,
        ]);

        return $abastecimento;
    }
}
