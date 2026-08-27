<?php

namespace App\Jobs;

use App\Models\CteEmailRequest;
use App\Models\User;
use App\Services\Bugio\CteEmailQueueService;
use App\Services\CteService\CteService;
use App\Services\NotificacaoService as notify;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SolicitarCteBugio implements ShouldQueue
{
    use Queueable;

    public $tries = 10;

    public $timeout = 300;

    private const LOCK_KEY = 'cte:email-queue:send';

    /**
     * @param  int|array<string, mixed>  $data
     */
    public function __construct(protected int|array $data) {}

    public function handle(CteEmailQueueService $queueService): void
    {
        Cache::lock(self::LOCK_KEY, 120)->block(10, function () use ($queueService): void {
            // Jobs queued before the persisted queue was introduced carry the original payload.
            if (is_array($this->data)) {
                $queueService->enqueue($this->data);

                return;
            }

            $request = CteEmailRequest::query()->findOrFail($this->data);

            if ($request->status !== 'pending_send') {
                return;
            }

            $scheduledAt = $request->scheduled_at;
            if ($scheduledAt && $scheduledAt->isFuture()) {
                $this->release(now()->diffInSeconds($scheduledAt));

                return;
            }

            $lastSentAt = CteEmailRequest::query()->max('sent_at');
            $delaySeconds = $queueService->delaySeconds();

            if ($lastSentAt && $delaySeconds > 0) {
                $nextAllowedAt = Carbon::parse($lastSentAt)->addSeconds($delaySeconds);

                if ($nextAllowedAt->isFuture()) {
                    $request->update(['scheduled_at' => $nextAllowedAt]);
                    $this->release(now()->diffInSeconds($nextAllowedAt));

                    return;
                }
            }

            $claimed = CteEmailRequest::query()
                ->whereKey($request->id)
                ->where('status', 'pending_send')
                ->update([
                    'status' => 'sending',
                    'error_message' => null,
                ]);

            if ($claimed === 0) {
                return;
            }

            $request->refresh();

            Log::info('Iniciando job de solicitação de CTe', [
                'cte_email_request_id' => $request->id,
                'documento_transporte' => $request->documento_transporte,
                'attempt' => $this->attempts(),
            ]);

            $service = new CteService;
            $service->solicitarCtePorEmail($request);

            if ($service->hasError()) {
                throw new \RuntimeException(implode('; ', $service->getErrors()));
            }

            Log::info('Solicitação de CTe enviada com sucesso', [
                'cte_email_request_id' => $request->id,
                'attempt' => $this->attempts(),
            ]);

            notify::success(
                'Solicitação de CTe enviada com sucesso!',
                'Documento: '.($request->documento_transporte ?? 'N/A'),
                true,
                $request->created_by
            );
        });
    }

    public function failed(\Throwable $exception): void
    {
        $request = is_int($this->data) ? CteEmailRequest::query()->find($this->data) : null;
        if (in_array($request?->status, ['pending_send', 'sending'], true)) {
            $request->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }

        Log::error('Job de solicitação de CTe falhou após todas as tentativas', [
            'cte_email_request_id' => $request?->id,
            'error' => $exception->getMessage(),
        ]);

        if ($request?->created_by) {
            notify::error('Erro ao solicitar CTe', 'Documento: '.($request->documento_transporte ?? 'N/A'), true, $request->created_by);
        }

        foreach (User::where('is_admin', true)->get() as $admin) {
            notify::error('Admin - Erro ao solicitar CTe', 'Documento: '.($request?->documento_transporte ?? 'N/A'), true, $admin);
        }
    }
}
