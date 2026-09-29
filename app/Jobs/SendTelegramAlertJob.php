<?php

namespace App\Jobs;

use App\Models\TelegramAlert;
use App\Models\TelegramAlertRecipient;
use App\Services\TelegramMessageFormatter;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SendTelegramAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $alertId) {}

    public function handle(TelegramService $telegram, TelegramMessageFormatter $formatter): void
    {
        $alert = TelegramAlert::query()->with('recipients')->findOrFail($this->alertId);
        $alert->update(['status' => 'sending', 'error' => null]);

        $message = $formatter->format((string) $alert->message_html);

        if ($message === '') {
            throw new RuntimeException('A mensagem do alerta ficou vazia após a formatação.');
        }

        $options = (array) $alert->telegram_options;
        $messageOptions = [
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => (bool) ($options['disable_web_page_preview'] ?? true),
            'disable_notification' => (bool) ($options['disable_notification'] ?? false),
            'protect_content' => (bool) ($options['protect_content'] ?? false),
        ];

        $buttons = collect($options['buttons'] ?? [])
            ->filter(fn (array $button): bool => filled($button['text'] ?? null) && filled($button['url'] ?? null))
            ->map(fn (array $button): array => [
                'text' => $button['text'],
                'url' => $button['url'],
            ])
            ->values()
            ->all();

        if ($buttons !== []) {
            $messageOptions['reply_markup'] = [
                'inline_keyboard' => [$buttons],
            ];
        }

        $documentOptions = [
            'disable_notification' => $messageOptions['disable_notification'],
            'protect_content' => $messageOptions['protect_content'],
        ];

        $hasFailures = false;

        foreach ($alert->recipients as $recipient) {
            if ($recipient->status === 'sent') {
                continue;
            }

            try {
                $this->sendToRecipient(
                    $telegram,
                    $alert,
                    $recipient,
                    $message,
                    $messageOptions,
                    $documentOptions,
                );
            } catch (Throwable $exception) {
                $hasFailures = true;
                $recipient->update([
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ]);

                Log::warning('Falha ao enviar alerta pelo Telegram', [
                    'alert_id' => $alert->id,
                    'recipient_id' => $recipient->id,
                    'chat_id' => $recipient->chat_id,
                    'erro' => $exception->getMessage(),
                ]);
            }
        }

        $sentCount = $alert->recipients()->where('status', 'sent')->count();
        $failedCount = $alert->recipients()->where('status', 'failed')->count();
        $status = $failedCount === 0 ? 'sent' : ($sentCount > 0 ? 'partial' : 'failed');

        $alert->update([
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
            'error' => $failedCount > 0 ? "{$failedCount} destinatário(s) não receberam o alerta." : null,
        ]);

        if ($status === 'sent' && filled($alert->file_path)) {
            Storage::disk($alert->file_disk)->delete($alert->file_path);
        }

        if ($hasFailures) {
            throw new RuntimeException("{$failedCount} destinatário(s) não receberam o alerta.");
        }
    }

    public function failed(Throwable $exception): void
    {
        $alert = TelegramAlert::find($this->alertId);

        if (! $alert) {
            return;
        }

        $sentCount = $alert->recipients()->where('status', 'sent')->count();
        $alert->update([
            'status' => $sentCount > 0 ? 'partial' : 'failed',
            'error' => $exception->getMessage(),
        ]);
    }

    private function sendToRecipient(
        TelegramService $telegram,
        TelegramAlert $alert,
        TelegramAlertRecipient $recipient,
        string $message,
        array $messageOptions,
        array $documentOptions,
    ): void {
        $recipient->update([
            'status' => 'sending',
            'error' => null,
        ]);

        if (blank($recipient->telegram_message_id)) {
            $response = $telegram->sendMessage($recipient->chat_id, $message, $messageOptions);
            $recipient->update([
                'telegram_message_id' => (string) data_get($response, 'result.message_id'),
                'message_sent_at' => now(),
            ]);
        }

        if (filled($alert->file_path) && blank($recipient->telegram_document_message_id)) {
            if (! Storage::disk($alert->file_disk)->exists($alert->file_path)) {
                throw new RuntimeException('O arquivo anexado não está mais disponível.');
            }

            $response = $telegram->sendDocument(
                $recipient->chat_id,
                $alert->file_path,
                $alert->file_disk,
                $documentOptions,
            );
            $recipient->update([
                'telegram_document_message_id' => (string) data_get($response, 'result.message_id'),
                'document_sent_at' => now(),
            ]);
        }

        $recipient->update([
            'status' => 'sent',
            'error' => null,
        ]);
    }
}
