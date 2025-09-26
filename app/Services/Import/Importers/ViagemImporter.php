<?php

namespace App\Services\Import\Importers;

use App\Models;
use App\Enum;
use App\Contracts\ExcelImportInterface;
use App\Services;
use App\Traits\UserCheckTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ViagemImporter implements ExcelImportInterface
{
    public function __construct(
        private Services\Viagem\ViagemService $viagemService,
        private Services\Integrado\IntegradoService $integradoService,
        private Services\Veiculo\VeiculoService $veiculoService
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
        $veiculo_id         = $this->veiculoService->getVeiculoIdByPlaca($row['Placa']);
        $codigoIntegrado    = $this->integradoService->extrairCodigoIntegrado($row['Destino']);
        $integrado          = $this->integradoService->getIntegradoByCodigo($codigoIntegrado);

        $km_pago = (float) str_replace(',', '.', $row['Km Sugerida']);

        return [
            'veiculo_id'            => $veiculo_id,
            'numero_viagem'         => $row['Viagem'],
            'documento_transporte'  => $row['Carga Cliente'] ?? null,
            'data_competencia'      => Carbon::createFromFormat('d/m/Y H:i', $row['Fim'])->format('Y-m-d'),
            'data_inicio'           => Carbon::createFromFormat('d/m/Y H:i', $row['Inicio'])->format('Y-m-d H:i'),
            'data_fim'              => Carbon::createFromFormat('d/m/Y H:i', $row['Fim'])->format('Y-m-d H:i'),
            'destino'               => $integrado ?? null,
            'km_rodado'             => is_numeric($row['Km Rodado']) ? (float) $row['Km Rodado'] : 0,
            'km_pago'               => is_numeric($km_pago) ? (float) $km_pago : 0,
            'km_cadastro'           => $integrado->km_rota ?? 0,
            'km_cobrar'             => 0,
            'motivo_divergencia'    => Enum\MotivoDivergenciaViagem::SEM_OBS->value,
            'conferido'             => false,
        ];
    }

    public function process(array $transformedData): mixed
    {
        Log::debug(__METHOD__.'@'.__LINE__, ['data' => $transformedData]);

        $viagem = $this->viagemService->updateOrCreate($transformedData);

        if($this->viagemService->hasError()){
            Log::error('Erro ao importar viagem', [
                'data' => $transformedData,
                'errors' => $this->viagemService->getMessage()
            ]);
            return null;
        }

        Log::debug(__METHOD__.'@'.__LINE__, ['viagem_id' => $viagem->id]);

        return $viagem;
    }



}


