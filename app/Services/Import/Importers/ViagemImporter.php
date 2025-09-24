<?php

namespace App\Services\Import\Importers;

use App\Models;
use App\Contracts\ExcelImportInterface;
use App\Services\Viagem\ViagemService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ViagemImporter implements ExcelImportInterface
{
    public function __construct(
        private ViagemService $viagemService
    )
    {}

    public function getRequiredColumns(): array
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

    public function getOptionalColumns(): array
    {
        return [];
    }

    public function validate(array $row, int $rowNumber): array
    {
        $errors = [];

        // Validação básica
        $validator = Validator::make($row, [
            'Viagem'            => 'required|string',
            'Carga Cliente'     => 'nullable|string',
            'Destino'           => 'required|string',
            'Placa'             => 'required|string',
            'Condutor Viagem'   => 'required|string',
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

        return $errors;
    }

    public function transform(array $row): array
    {
        $veiculo = Models\Veiculo::where('placa', $row['Placa'])->first();

        return [
            'veiculo_id'            => $veiculo->id,
            'numero_viagem'         => $row['Viagem'],
            'documento_transporte'  => $row['Carga Cliente'] ?? null,
            'data_inicio'           => Carbon::createFromFormat('d/m/Y H:i', $row['Inicio'])->format('Y-m-d H:i'),
            'data_fim'              => Carbon::createFromFormat('d/m/Y H:i', $row['Fim'])->format('Y-m-d H:i'),
            'destino'               => $row['Destino'] ?? null,
            'km_rodado'             => is_numeric($row['Km Rodado']) ? (float) $row['Km Rodado'] : 0,
            'km_pago'               => is_numeric($row['Km Sugerida']) ? (float) $row['Km Sugerida'] : 0,
        ];
    }

    public function process(array $transformedData): mixed
    {
        dd($transformedData);
        return $this->viagemService->create($transformedData);
        return false;
    }



}


