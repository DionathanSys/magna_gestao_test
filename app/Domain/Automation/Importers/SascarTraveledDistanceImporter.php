<?php

namespace App\Domain\Automation\Importers;

use App\Domain\Automation\AutomationReportRegistry;
use App\Domain\Automation\Contracts\AutomationResultImporter;
use App\Domain\Automation\Data\AutomationImportPageResult;
use App\Models\AutomationJob;
use App\Models\HistoricoQuilometragem;
use App\Models\Veiculo;
use App\Services\HistoricoQuilometragem\HistoricoQuilometragemService;
use App\Services\MailInbound\Support\DocumentIdentity;
use App\Services\Veiculo\VeiculoCacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SascarTraveledDistanceImporter implements AutomationResultImporter
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
            $item = is_array($item) ? $item : [];
            $normalized = [];
            $vehicle = null;

            try {
                $normalized = $this->normalizeItem($item, (array) ($job->parameters ?? []));

                Validator::make($normalized, [
                    'placa' => 'required|string|max:20',
                    'quilometragem' => 'required|integer|min:0',
                    'data_referencia' => 'required|date',
                ])->validate();

                $vehicle = $this->resolveVehicle($normalized['placa']);
                $operation = $this->persistKilometrage($vehicle, $normalized);

                if ($operation === 'created') {
                    $created++;
                } elseif ($operation === 'updated') {
                    $updated++;
                } else {
                    $ignored++;
                }
            } catch (Throwable $exception) {
                $ignored++;
                $errors[] = [
                    'index' => $index,
                    'placa' => $normalized['placa'] ?? $this->firstFilled($item, [
                        'placa',
                        'plate',
                        'vehicle_plate',
                        'vehicle_license_plate',
                        'license_plate',
                        'vehicle.placa',
                        'vehicle.plate',
                        'vehicle',
                    ]),
                    'data_referencia' => $normalized['data_referencia'] ?? null,
                    'quilometragem' => $normalized['quilometragem'] ?? null,
                    'veiculo_id' => $vehicle?->id,
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
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item, array $parameters): array
    {
        return [
            'placa' => $this->firstFilled($item, [
                'placa',
                'plate',
                'vehicle_plate',
                'vehicle_license_plate',
                'license_plate',
                'vehicle.placa',
                'vehicle.plate',
                'vehicle',
            ]),
            'quilometragem' => $this->normalizeKilometrage($this->firstFilled($item, [
                'quilometragem',
                'km_atual',
                'km',
                'odometro',
                'odometer',
                'mileage',
                'traveled_distance',
                'distance_traveled',
                'distancia_percorrida',
                'km_percorrido',
                'km_rodado',
                'distance',
            ])),
            'data_referencia' => $this->firstFilled($item, [
                'data_referencia',
                'reference_date',
                'data',
                'date',
                'dia',
                'day',
            ]) ?? ($parameters['from'] ?? null),
        ];
    }

    private function persistKilometrage(Veiculo $vehicle, array $normalized): string
    {
        $date = Carbon::parse($normalized['data_referencia'])->toDateString();
        $kilometrage = (int) $normalized['quilometragem'];

        $operation = DB::transaction(function () use ($vehicle, $date, $kilometrage): string {
            $sameDate = HistoricoQuilometragem::query()
                ->where('veiculo_id', $vehicle->id)
                ->whereDate('data_referencia', $date)
                ->orderByDesc('id')
                ->first();

            $lastKilometrage = HistoricoQuilometragem::query()
                ->where('veiculo_id', $vehicle->id)
                ->whereDate('data_referencia', '<=', $date)
                ->orderByDesc('data_referencia')
                ->orderByDesc('id')
                ->value('quilometragem');

            if ($lastKilometrage !== null && $kilometrage < (int) $lastKilometrage) {
                throw new \InvalidArgumentException(
                    'A quilometragem nao pode ser menor que a ultima registrada ate a data de referencia.'
                );
            }

            if ($sameDate) {
                if ((int) $sameDate->quilometragem === $kilometrage) {
                    return 'ignored';
                }

                $sameDate->update(['quilometragem' => $kilometrage]);

                return 'updated';
            }

            $service = new HistoricoQuilometragemService;
            $history = $service->registrar([
                'veiculo_id' => $vehicle->id,
                'data_referencia' => $date,
                'quilometragem' => $kilometrage,
            ]);

            if (! $history) {
                throw new \RuntimeException(
                    $service->getMessageUser() ?: 'Falha ao registrar a quilometragem.'
                );
            }

            return 'created';
        });

        if ($operation !== 'ignored') {
            VeiculoCacheService::invalidarCacheVeiculos($vehicle->id);
        }

        return $operation;
    }

    private function resolveVehicle(string $plate): Veiculo
    {
        $normalizedPlate = DocumentIdentity::normalizePlate($plate);

        if ($normalizedPlate === null) {
            throw new \InvalidArgumentException('Placa nao informada ou invalida.');
        }

        $vehicle = Veiculo::query()
            ->select('id', 'placa')
            ->where('is_active', true)
            ->where(function ($query) use ($plate, $normalizedPlate): void {
                $query->where('placa', trim($plate))
                    ->orWhere('placa', $normalizedPlate);
            })
            ->first();

        if (! $vehicle) {
            throw new \InvalidArgumentException("Veiculo ativo nao encontrado para a placa {$normalizedPlate}.");
        }

        return $vehicle;
    }

    private function normalizeKilometrage(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $value = trim(str_replace(' ', '', $value));

        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace('.', '', str_replace(',', '.', $value))
                : str_replace(',', '', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d+\.\d{3}$/', $value) === 1) {
            $value = str_replace('.', '', $value);
        }

        if (is_numeric($value) && (float) $value === floor((float) $value)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $keys
     */
    private function firstFilled(array $item, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($item, $key);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
