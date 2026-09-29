<?php

namespace App\Jobs\Telegram;

use App\Services\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarMensagemTelegram implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $chatId,
        public string $message,
    ) {
        $this->onQueue((string) config('services.telegram.queue', 'integracoes'));
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(TelegramService $telegram): void
    {
        $telegram->sendMessage($this->chatId, $this->message);
    }
}
