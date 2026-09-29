<?php

namespace App\Jobs\Telegram;

use App\Enum\Lembrete\StatusLembreteEnum;
use App\Models\Lembrete;
use App\Services\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnviarLembreteTelegram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $lembreteId)
    {
        $this->onQueue((string) config('services.telegram.queue', 'integracoes'));
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(TelegramService $telegram): void
    {
        $lembrete = Lembrete::query()->with('user')->find($this->lembreteId);

        if (! $lembrete || in_array($lembrete->status, [
            StatusLembreteEnum::ENVIADO,
            StatusLembreteEnum::CANCELADO,
        ], true)) {
            return;
        }

        $chatId = trim((string) $lembrete->user?->telegram_chat_id);

        if ($chatId === '') {
            $lembrete->update([
                'status' => StatusLembreteEnum::FALHOU,
                'error_message' => 'O usuário destinatário não possui Telegram Chat ID configurado.',
            ]);

            return;
        }

        $message = $lembrete->titulo;

        if (filled($lembrete->mensagem)) {
            $message .= "\n\n".$lembrete->mensagem;
        }

        $telegram->sendMessage($chatId, $message);

        $lembrete->update([
            'status' => StatusLembreteEnum::ENVIADO,
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Lembrete::query()
            ->whereKey($this->lembreteId)
            ->whereNotIn('status', [
                StatusLembreteEnum::ENVIADO->value,
                StatusLembreteEnum::CANCELADO->value,
            ])
            ->update([
                'status' => StatusLembreteEnum::FALHOU->value,
                'error_message' => $exception->getMessage(),
            ]);

        Log::error('Falha definitiva ao enviar lembrete pelo Telegram', [
            'lembrete_id' => $this->lembreteId,
            'erro' => $exception->getMessage(),
        ]);
    }
}
