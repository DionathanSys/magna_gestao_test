<?php

namespace App\Services\Bugio;

use App\DTO\PayloadCteDTO;
use App\Jobs\SolicitarCteBugio;
use App\Mail\SolicitacaoCteMail;
use App\Models\CteEmailRequest;
use App\Models\Integrado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CteEmailQueueService
{
    private const LOCK_KEY = 'cte:email-queue:schedule';

    public function enqueue(
        array $data,
        string $origin = CteEmailRequest::ORIGIN_MANUAL,
        ?int $shipmentDocumentGroupId = null,
    ): CteEmailRequest {
        return Cache::lock(self::LOCK_KEY, 10)->block(5, function () use ($data, $origin, $shipmentDocumentGroupId): CteEmailRequest {
            $data = $this->preparePayload($data);

            if ((float) ($data['valor_frete'] ?? 0) <= 0) {
                throw new \InvalidArgumentException('Não é possível solicitar CTe com valor de frete zero.');
            }

            if ($this->containsGuatambuMunicipio($data)) {
                throw new \DomainException('Não é possível solicitar CTe para integrado com município Guatambu.');
            }

            $payload = PayloadCteDTO::fromArray($data);
            $mail = new SolicitacaoCteMail($payload);
            $scheduledAt = $this->nextScheduledAt();

            $request = app(CteEmailRequestService::class)
                ->createPendingRequest(
                    $payload,
                    $mail,
                    $data,
                    $scheduledAt,
                    $origin,
                    $shipmentDocumentGroupId,
                );

            SolicitarCteBugio::dispatch($request->id)
                ->onConnection('database')
                ->delay($scheduledAt);

            return $request;
        });
    }

    public function delaySeconds(): int
    {
        if (! db_config('config-bugio.cte-email-delay-enabled', true)) {
            return 0;
        }

        return max(1, (int) db_config('config-bugio.cte-email-delay-minutes', 4)) * 60;
    }

    private function preparePayload(array $data): array
    {
        $data['motorista']['nome'] = collect(db_config('config-bugio.motoristas'))
            ->firstWhere('cpf', $data['motorista']['cpf'] ?? null)['motorista'] ?? ($data['motorista']['nome'] ?? null);
        $data['valor_frete'] = $data['valor_frete'] ?? ((float) ($data['km_total'] ?? 0) * db_config('config-bugio.valor-quilometro', 0));

        return $data;
    }

    private function containsGuatambuMunicipio(array $data): bool
    {
        $municipios = collect($data['destinos'] ?? [])
            ->map(fn (mixed $destino): string => is_array($destino) ? (string) ($destino['integrado_municipio'] ?? '') : '')
            ->filter()
            ->all();

        $ids = collect($data['destinos'] ?? [])
            ->map(fn (mixed $destino): ?int => is_array($destino) && isset($destino['integrado_id']) ? (int) $destino['integrado_id'] : null)
            ->filter()
            ->values();

        if (isset($data['integrado_id'])) {
            $ids->push((int) $data['integrado_id']);
        }

        if ($ids->isNotEmpty()) {
            $municipios = array_merge($municipios, Integrado::query()->whereIn('id', $ids->all())->pluck('municipio')->all());
        }

        return collect($municipios)->contains(function (string $municipio): bool {
            $normalized = Str::of($municipio)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]/', '')
                ->toString();

            return str_contains($normalized, 'guatambu');
        });
    }

    private function nextScheduledAt(): Carbon
    {
        $now = now();
        $delaySeconds = $this->delaySeconds();

        if ($delaySeconds === 0) {
            return $now;
        }

        $lastSentAt = CteEmailRequest::query()->max('sent_at');
        $lastScheduledAt = CteEmailRequest::query()
            ->where('status', 'pending_send')
            ->max('scheduled_at');
        $nextAvailableAt = $now->copy();

        if ($lastSentAt) {
            $lastSentNextAllowedAt = Carbon::parse($lastSentAt)->addSeconds($delaySeconds);
            $nextAvailableAt = $lastSentNextAllowedAt->greaterThan($nextAvailableAt)
                ? $lastSentNextAllowedAt
                : $nextAvailableAt;
        }

        if ($lastScheduledAt) {
            $lastScheduledNextAllowedAt = Carbon::parse($lastScheduledAt)->addSeconds($delaySeconds);
            $nextAvailableAt = $lastScheduledNextAllowedAt->greaterThan($nextAvailableAt)
                ? $lastScheduledNextAllowedAt
                : $nextAvailableAt;
        }

        return $nextAvailableAt;
    }
}
