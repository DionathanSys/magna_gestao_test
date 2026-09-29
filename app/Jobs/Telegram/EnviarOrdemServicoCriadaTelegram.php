<?php

namespace App\Jobs\Telegram;

use App\Models\OrdemServico;
use App\Models\User;
use App\Services\Alertas\TelegramAlertConfigService;
use App\Services\Telegram\OrdemServicoTelegramMessageService;
use App\Services\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnviarOrdemServicoCriadaTelegram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $ordemServicoId,
        public int $destinatarioId,
    ) {
        $this->onQueue((string) config('services.telegram.queue', 'integracoes'));
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(
        TelegramService $telegram,
        TelegramAlertConfigService $alertConfig,
        OrdemServicoTelegramMessageService $messageService,
    ): void {
        if (! $alertConfig->isEnabled(TelegramAlertConfigService::ORDEM_SERVICO_CRIADA)) {
            return;
        }

        if (! in_array(
            $this->destinatarioId,
            $alertConfig->recipientIds(TelegramAlertConfigService::ORDEM_SERVICO_CRIADA),
            true,
        )) {
            return;
        }

        $user = User::query()->find($this->destinatarioId);
        $ordemServico = OrdemServico::query()->find($this->ordemServicoId);

        if (! $user || blank($user->telegram_chat_id) || ! $ordemServico) {
            return;
        }

        $telegram->sendMessage(
            (string) $user->telegram_chat_id,
            $messageService->make($ordemServico),
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falha definitiva ao enviar alerta de OS criada pelo Telegram', [
            'ordem_servico_id' => $this->ordemServicoId,
            'destinatario_id' => $this->destinatarioId,
            'erro' => $exception->getMessage(),
        ]);
    }
}
