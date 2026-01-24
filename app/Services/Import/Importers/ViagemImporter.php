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
            'Viagem'            => 'required|string',
            'Carga Cliente'     => 'nullable|string',
            'Destino'           => 'nullable|string',
            'Quantidade'        => 'integer',
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
            'Fim.date_format'           => 'A Data de Fim deve estar no formato dd/mm/aaaa hh:mm ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
            'Quantidade.integer'           => 'O Destino deve ser um número inteiro ' . ($row['numero_viagem'] ?? 'linha ' . $rowNumber),
        ]);

        if ($validator->fails()) {
            Log::error('Validação falhou para a linha ' . $rowNumber, [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'row' => $row,
                'errors' => $validator->errors()->all(),
            ]);
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

            $veiculo            = $this->veiculoService->getVeiculoByPlaca($row['Placa']);
            $veiculo_id         = $veiculo->id;
            $unidade_negocio    = $veiculo->filial;
            $cliente            = $veiculo->informacoes_complementares['cliente'] ?? null;

            $codigoIntegrado    = $this->integradoService->extrairCodigoIntegrado($row['Destino'] ?? '');

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
                
            ]);Log::error("Erro na busca da Placa/Integrado", [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'error' => $e->getMessage(),
                    'row' => $row
        }

        $km_pago = (float) str_replace(',', '.', $row['KmSugerida']);

        return [
            'veiculo_id'            => $veiculo_id,
            'unidade_negocio'       => $unidade_negocio,
            'cliente'               => $cliente,
            'numero_viagem'         => $row['Viagem'],
            'quantidade'            => $row['Quantidade'],
            'documento_transporte'  => $row['CargaCliente'] ?? null,
            'data_competencia'      => Carbon::createFromFormat('d/m/Y H:i', $row['Fim'])->format('Y-m-d'),
            'data_inicio'           => Carbon::createFromFormat('d/m/Y H:i', $row['Inicio'])->format('Y-m-d H:i'),
            'data_fim'              => Carbon::createFromFormat('d/m/Y H:i', $row['Fim'])->format('Y-m-d H:i'),
            'destino'               => $integrado ?? null,
            'km_rodado'             => is_numeric($row['KmRodado']) ? (float) $row['KmRodado'] : 0,
            'km_pago'               => is_numeric($km_pago) ? (float) $km_pago : 0,
            'km_cadastro'           => $integrado->km_rota ?? 0,
            'km_cobrar'             => 0,
            'motivo_divergencia'    => Enum\MotivoDivergenciaViagem::SEM_OBS->value,
            'conferido'             => false,
            'condutor'              => Str::upper($row['CondutorViagem'] ?? 'não informado'),
        ];
    }

    public function process(array $data, int $rowNumber): ?Models\Viagem
    {
        $errors = $this->validate($data, $rowNumber);

        if (!empty($errors)) {
            Log::error('Erros de validação na importação de viagem', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'row' => $rowNumber,
                'data' => $data,
                'errors' => $errors
            ]);
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);
            return null;
        }

        $transformedData = $this->transform($data);

        $viagem = $this->viagemService->create($transformedData);

        if ($this->viagemService->hasError()) {
            Log::error('Erro ao importar viagem', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'data' => $transformedData,
                'errors' => $this->viagemService->getErrors()
            ]);
            $this->setError("Erro na linha {$rowNumber}.", $this->viagemService->getErrors());
            return null;
        }

        return $viagem;
    }
}
