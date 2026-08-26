<?php

namespace App\Services\Bugio;

use App\DTO\PayloadCteDTO;
use App\Jobs\SolicitarCteBugio;
use App\Mail\SolicitacaoCteMail;
use App\Models\CteEmailRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CteEmailQueueService
{
    private const LOCK_KEY = 'cte:email-queue:schedule';

    public function enqueue(array $data): CteEmailRequest
    {
        return Cache::lock(self::LOCK_KEY, 10)->block(5, function () use ($data): CteEmailRequest {
            $data = $this->preparePayload($data);
            $payload = PayloadCteDTO::fromArray($data);
            $mail = new SolicitacaoCteMail($payload);
            $scheduledAt = $this->nextScheduledAt();

            $request = app(CteEmailRequestService::class)
                ->createPendingRequest($payload, $mail, $data, $scheduledAt);

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
