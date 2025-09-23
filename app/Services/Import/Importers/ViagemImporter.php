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
            'Placa Veiculo' => 'required|string',
            'Numero Documento' => 'required|string',
            'Data Emissao' => 'required|date_format:d/m/Y',
            'KM Inicial' => 'required|numeric|min:0',
            'KM Final' => 'required|numeric|min:0',
            'Valor Frete' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $errors = array_merge($errors, $validator->errors()->all());
        }

        // Validações específicas de negócio
        if (!empty($row['Placa Veiculo'])) {
            $veiculo = Models\Veiculo::where('placa', $row['Placa Veiculo'])->first();
            if (!$veiculo) {
                $errors[] = "Veículo com placa '{$row['Placa Veiculo']}' não encontrado.";
            }
        }

        if (!empty($row['KM Inicial']) && !empty($row['KM Final'])) {
            if ($row['KM Final'] <= $row['KM Inicial']) {
                $errors[] = "KM Final deve ser maior que KM Inicial.";
            }
        }

        // Verificar duplicata
        if (!empty($row['Numero Documento'])) {
            $exists = Models\DocumentoFrete::where('numero_documento', $row['Numero Documento'])->exists();
            if ($exists) {
                $errors[] = "Documento '{$row['Numero Documento']}' já existe.";
            }
        }

        return $errors;
    }

    public function transform(array $row): array
    {
        $veiculo = Models\Veiculo::where('placa', $row['Placa Veiculo'])->first();

        return [
            'veiculo_id' => $veiculo->id,
            'numero_documento' => $row['Numero Documento'],
            'data_emissao' => Carbon::createFromFormat('d/m/Y', $row['Data Emissao'])->format('Y-m-d'),
            'km_inicial' => (int) $row['KM Inicial'],
            'km_final' => (int) $row['KM Final'],
            'valor_frete' => (float) str_replace(',', '.', str_replace('.', '', $row['Valor Frete'])),
            'origem' => $row['Origem'] ?? null,
            'destino' => $row['Destino'] ?? null,
            'cliente' => $row['Cliente'] ?? null,
            'observacoes' => $row['Observacoes'] ?? null,
        ];
    }

    public function process(array $transformedData): mixed
    {
        // return $this->viagemService->($transformedData);
        return false;
    }



}


