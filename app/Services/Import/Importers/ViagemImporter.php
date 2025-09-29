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

class ViagemImporter implements ExcelImportInterface
{
    use ServiceResponseTrait;

    public function __construct(
        private Services\Viagem\ViagemService       $viagemService,
        private Services\Integrado\IntegradoService $integradoService,
        private Services\Veiculo\VeiculoService     $veiculoService

    ) {}

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
            'Destino'           => 'nullable|string',
            'Placa'             => 'required|string',
            'Condutor Viagem'   => 'nullable|string',
            'Inicio'            => 'required|date_format:d/m/Y H:i',
            'Fim'               => 'required|date_format:d/m/Y H:i',
        ], [
            'Viagem.required'           => 'O campo Viagem é obrigatório ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Viagem.string'             => 'O campo Viagem deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Placa.required'            => 'A Placa é obrigatória ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Placa.string'              => 'A Placa deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Condutor Viagem.string'    => 'O Condutor da Viagem deve ser um texto válido ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Inicio.required'           => 'A Data de Início é obrigatória ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Inicio.date_format'        => 'A Data de Início deve estar no formato dd/mm/aaaa hh:mm ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Fim.required'              => 'A Data de Fim é obrigatória ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Fim.date_format'         => 'A Data de Fim deve estar no formato dd/mm/aaaa hh:mm ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
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
        try {

            $veiculo_id         = $this->veiculoService->getVeiculoIdByPlaca($row['Placa']);
            $codigoIntegrado    = $this->integradoService->extrairCodigoIntegrado($row['Destino']);

            $integrado = null;
            if ($codigoIntegrado) {
                $integrado = $this->integradoService->getIntegradoByCodigo($codigoIntegrado);
            } else {
                Log::alert("Nenhum código de integrado encontrado na string '{$row['Destino']}'");
            }

        } catch (\Exception $e) {
            Log::error("Erro na busca da Placa/Integrado", [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'error' => $e->getMessage(),
                'row' => $row
            ]);
        }

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
            'condutor'              => $row['Condutor Viagem'] ?? null,
        ];
    }

    public function process(array $data, int $rowNumber): ?Models\Viagem
    {
        $errors = $this->validate($data, $rowNumber);

        if (!empty($errors)) {
            Log::alert('Erros de validação na importação de viagem', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'row' => $rowNumber,
                'data' => $data,
                'errors' => $errors
            ]);
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);
            return null;
        }

        $transformedData = $this->transform($data);

        $viagem = $this->viagemService->updateOrCreate($transformedData);

        if ($this->viagemService->hasError()) {
            Log::error('Erro ao importar viagem', [
                'data' => $transformedData,
                'errors' => $this->viagemService->getMessage()
            ]);
            $this->setError("Erro na linha {$rowNumber}.", [$this->viagemService->getMessage()]);
            return null;
        }

        return $viagem;
    }
}
