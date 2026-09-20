<?php

namespace App\Domain\Automation\Importers;

use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\Contracts\AutomationResultImporter;
use App\Domain\Automation\Data\AutomationImportPageResult;
use App\Models\AutomationJob;
use App\Models\Veiculo;
use App\Services\Integrado\IntegradoDestinoService;
use App\Services\MailInbound\Support\DocumentIdentity;
use App\Services\Viagem\ViagemService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class DailyTripSummaryImporter implements AutomationResultImporter
{
    public function __construct(
        private readonly AutomationReportRegistry $reports,
    ) {}

    public function importPage(
        AutomationJob $job,
        array $items,
        array $meta,
    ): AutomationImportPageResult {
        $definition = $this->reports->get($job->report_key);
        $created = 0;
        $updated = 0;
        $ignored = 0;
        $errors = [];

        foreach ($items as $index => $item) {
            try {
                $normalized = $this->normalizeItem($item, $definition->defaultUnidadeNegocio);
                $validator = Validator::make($normalized, [
                    'numero_viagem' => 'required|string|max:255',
                    'placa' => 'required|string|max:20',
                    'unidade_negocio' => 'required|string|max:255',
                    'cliente' => 'nullable|string|max:255',
                    'destino' => 'nullable|string|max:255',
                    'km_rodado' => 'nullable|numeric|min:0',
                    'km_pago' => 'nullable|numeric|min:0',
                    'data_competencia' => 'required|date',
                    'data_inicio' => 'required|date',
                    'data_fim' => 'required|date|after_or_equal:data_inicio',
                    'possui_pendencia' => 'boolean',
                    'pendencias' => 'nullable|array',
                    'motoristas' => 'nullable|array',
                ]);

                if ($validator->fails()) {
                    throw new \InvalidArgumentException($validator->errors()->toJson());
                }

                $veiculoId = $this->resolveVehicleId($normalized['placa']);
                $service = new ViagemService;
                $viagem = DB::transaction(function () use ($normalized, $veiculoId, $service): mixed {
                    $data = [
                        'veiculo_id' => $veiculoId,
                        'unidade_negocio' => $normalized['unidade_negocio'],
                        'cliente' => $normalized['cliente'],
                        'numero_viagem' => $normalized['numero_viagem'],
                        'km_rodado' => $normalized['km_rodado'],
                        'km_pago' => $normalized['km_pago'],
                        'data_competencia' => $normalized['data_competencia'],
                        'data_inicio' => $normalized['data_inicio'],
                        'data_fim' => $normalized['data_fim'],
                        'possui_pendencia' => $normalized['possui_pendencia'],
                        'pendencias' => $normalized['pendencias'],
                        'motoristas' => $normalized['motoristas'],
                        'conferido' => false,
                        'ignorar' => false,
                    ];

                    $viagem = $service->updateOrCreate($data);

                    if ($service->hasError() || ! $viagem) {
                        throw new \RuntimeException($service->getMessageUser() ?: 'Falha ao persistir viagem.');
                    }

                    (new IntegradoDestinoService)->vincularCarga($viagem, $normalized['destino']);

                    return $viagem;
                });

                $action = (string) ($service->getData()['acao'] ?? 'processada');

                if ($action === 'criada') {
                    $created++;
                } elseif ($action === 'atualizada') {
                    $updated++;
                } else {
                    $ignored++;
                }
            } catch (Throwable $exception) {
                $ignored++;
                $errors[] = [
                    'index' => $index,
                    'numero_viagem' => $item['numero_viagem'] ?? null,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return new AutomationImportPageResult(
            recordsReceived: count($items),
            recordsCreated: $created,
            recordsUpdated: $updated,
            recordsIgnored: $ignored,
            errors: $errors,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item, ?string $defaultUnidadeNegocio): array
    {
        $dataInicio = $item['data_inicio'] ?? $item['inicio'] ?? null;
        $dataFim = $item['data_fim'] ?? $item['fim'] ?? null;
        $dataCompetencia = $item['data_competencia'] ?? $item['data'] ?? $dataInicio;
        $pendencias = $item['pendencias'] ?? [];

        if (is_string($pendencias) && filled($pendencias)) {
            $pendencias = [$pendencias];
        }

        return [
            'numero_viagem' => $item['numero_viagem'] ?? null,
            'placa' => $item['placa'] ?? null,
            'unidade_negocio' => $item['unidade_negocio'] ?? $defaultUnidadeNegocio,
            'cliente' => $item['cliente'] ?? null,
            'destino' => $item['destino'] ?? null,
            'km_rodado' => $item['km_rodado'] ?? null,
            'km_pago' => $item['km_pago'] ?? null,
            'data_competencia' => $dataCompetencia,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'possui_pendencia' => (bool) ($item['possui_pendencia'] ?? ! empty($pendencias)),
            'pendencias' => is_array($pendencias) ? $pendencias : [],
            'motoristas' => is_array($item['motoristas'] ?? null) ? $item['motoristas'] : [],
        ];
    }

    private function resolveVehicleId(?string $plate): int
    {
        $normalizedPlate = DocumentIdentity::normalizePlate($plate);

        if ($normalizedPlate === null) {
            throw new \InvalidArgumentException('Placa nao informada ou invalida.');
        }

        $vehicle = Veiculo::query()
            ->select('id', 'placa')
            ->where('is_active', true)
            ->where(function ($query) use ($plate, $normalizedPlate): void {
                $query->where('placa', trim((string) $plate))
                    ->orWhere('placa', $normalizedPlate);
            })
            ->first();

        if (! $vehicle) {
            throw new \InvalidArgumentException("Veiculo ativo nao encontrado para a placa {$normalizedPlate}.");
        }

        return (int) $vehicle->id;
    }
}
