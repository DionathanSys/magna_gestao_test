<?php

namespace App\Services\Import\Importers;

use App\Contracts\ExcelImportInterface;
use App\Models;
use App\Services;
use App\Traits\ServiceResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ViagemImporter implements ExcelImportInterface
{
    use ServiceResponseTrait;

    public function __construct(
        private Services\Viagem\ViagemService $viagemService,
        private Services\Integrado\IntegradoService $integradoService,
        private Services\Veiculo\VeiculoService $veiculoService

    ) {}

    public function getRequiredColumns(): array
    {
        return [
            'Viagem',
            'CargaCliente',
            'Destino',
            'Inicio',
            'Fim',
            'Placa',
            'CondutorViagem',
            'KmRodado',
            'KmSugerida',
            'Quantidade',
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
            'Viagem' => 'required|string',
            'CargaCliente' => 'nullable|string',
            'Destino' => 'nullable|string',
            'Quantidade' => 'integer',
            'Placa' => 'required|string',
            'CondutorViagem' => 'nullable|string',
            'Inicio' => 'required|date_format:d/m/Y H:i',
            'Fim' => 'required|date_format:d/m/Y H:i',
        ], [
            'Viagem.required' => 'O campo Viagem é obrigatório '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'Viagem.string' => 'O campo Viagem deve ser um texto válido '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'Placa.required' => 'A Placa é obrigatória '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'Placa.string' => 'A Placa deve ser um texto válido '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'CondutorViagem.string' => 'O Condutor da Viagem deve ser um texto válido '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'Inicio.required' => 'A Data de Início é obrigatória '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'Inicio.date_format' => 'A Data de Início deve estar no formato dd/mm/aaaa hh:mm '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'Fim.required' => 'A Data de Fim é obrigatória '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'Fim.date_format' => 'A Data de Fim deve estar no formato dd/mm/aaaa hh:mm '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
            'Quantidade.integer' => 'O Destino deve ser um número inteiro '.($row['numero_viagem'] ?? 'linha '.$rowNumber),
        ]);

        if ($validator->fails()) {
            Log::error('Validação falhou para a linha '.$rowNumber, [
                'metodo' => __METHOD__.'@'.__LINE__,
                'row' => $row,
                'errors' => $validator->errors()->all(),
            ]);
            $errors = array_merge($errors, $validator->errors()->all());
        }

        // Validações específicas de negócio
        if (! empty($row['Placa'])) {
            $veiculo = Models\Veiculo::where('placa', $row['Placa'])->first();
            if (! $veiculo) {
                $errors[] = "Veículo com placa '{$row['Placa']}' não encontrado.";
            }
        }

        return $errors;
    }

    public function transform(array $row): array
    {
        Log::info('Dados recebidos pelo ViagemImporter', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'numero_viagem' => $row['Viagem'] ?? null,
            'headers' => array_keys($row),
            'row' => $row,
        ]);

        $veiculo = $this->veiculoService->getVeiculoByPlaca($row['Placa']);
        $codigoIntegrado = $this->integradoService->extrairCodigoIntegrado((string) ($row['Destino'] ?? ''));
        $integrado = filled($codigoIntegrado) ? $this->integradoService->getIntegradoByCodigo((string) $codigoIntegrado) : null;

        $rawKmRodado = $row['KmRodado'] ?? null;
        $rawKmPago = $row['KmSugerida'] ?? ($row['KmSugerida '] ?? null);

        $kmRodado = $this->parseDecimal($rawKmRodado);
        $kmPago = $this->parseDecimal($rawKmPago);

        Log::info('Valores brutos do relatorio de viagem para quilometragem', [
            'metodo' => __METHOD__.'@'.__LINE__,
            'numero_viagem' => $row['Viagem'] ?? null,
            'placa' => $row['Placa'] ?? null,
            'raw_km_rodado' => $rawKmRodado,
            'raw_km_pago' => $rawKmPago,
            'parsed_km_rodado' => $kmRodado,
            'parsed_km_pago' => $kmPago,
            'headers' => array_keys($row),
        ]);

        $pendencias = [];

        if ($kmPago <= 0) {
            $pendencias['sem_km_pago'] = 'Sem km pago';
        }

        if ($kmRodado <= 0) {
            $pendencias['sem_km_rodado'] = 'Sem km rodado';
        }

        if ($kmRodado > 1000) {
            $pendencias['km_acima_limite'] = 'Km acima do limite';
        }

        if (! $integrado) {
            $pendencias['sem_integrado'] = 'Sem integrado';
        }

        return [
            'veiculo_id' => $veiculo?->id,
            'unidade_negocio' => $veiculo?->filial,
            'cliente' => $veiculo?->informacoes_complementares['cliente'] ?? null,
            'numero_viagem' => $row['Viagem'],
            'numero_interno' => null,
            'documento_transporte' => $row['CargaCliente'] ?? null,
            'data_competencia' => Carbon::createFromFormat('d/m/Y H:i', $row['Fim'])->format('Y-m-d'),
            'data_inicio' => Carbon::createFromFormat('d/m/Y H:i', $row['Inicio'])->format('Y-m-d H:i'),
            'data_fim' => Carbon::createFromFormat('d/m/Y H:i', $row['Fim'])->format('Y-m-d H:i'),
            'total_destinos' => is_numeric($row['Quantidade'] ?? null) ? (int) $row['Quantidade'] : 0,
            'destino' => $integrado ?? null,
            'km_rodado' => $kmRodado,
            'km_pago' => $kmPago,
            'conferido' => false,
            'ignorar' => false,
            'possui_pendencia' => ! empty($pendencias),
            'pendencias' => $pendencias,
            'motorista1' => null,
            'motorista2' => null,
        ];
    }

    private function parseDecimal(mixed $value): float
    {
        if ($value === null) {
            return 0;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return 0;
        }

        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : 0;
    }

    public function process(array $data, int $rowNumber): ?Models\Viagem
    {
        $errors = $this->validate($data, $rowNumber);

        if (! empty($errors)) {
            Log::error('Erros de validação na importação de viagem', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'row' => $rowNumber,
                'data' => $data,
                'errors' => $errors,
            ]);
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);

            return null;
        }

        $transformedData = $this->transform($data);

        $viagem = $this->viagemService->create($transformedData);

        if ($viagem && ($transformedData['destino'] ?? null) instanceof Models\Integrado) {
            (new Services\Carga\CargaService)->create($transformedData['destino'], $viagem);
        }

        if ($this->viagemService->hasError()) {
            Log::error('Erro ao importar viagem', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'data' => $transformedData,
                'errors' => $this->viagemService->getErrors(),
            ]);
            $this->setError("Erro na linha {$rowNumber}.", $this->viagemService->getErrors());

            return null;
        }

        return $viagem;
    }
}
