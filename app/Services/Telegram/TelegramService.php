<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $chatId, string $message): array
    {
        $token = trim((string) config('services.telegram.bot_token'));

        if (! (bool) config('services.telegram.enabled', false) || $token === '') {
            throw new RuntimeException('A integração com o Telegram não está configurada.');
        }

        if (trim($chatId) === '') {
            throw new RuntimeException('O destinatário do Telegram não foi informado.');
        }

        $response = Http::baseUrl(rtrim((string) config('services.telegram.api_url'), '/'))
            ->timeout((int) config('services.telegram.timeout_seconds', 10))
            ->connectTimeout((int) config('services.telegram.connect_timeout_seconds', 5))
            ->retry(
                max(1, (int) config('services.telegram.retry_times', 3)),
                max(0, (int) config('services.telegram.retry_sleep_milliseconds', 250)),
            )
            ->post('/bot'.$token.'/sendMessage', [
                'chat_id' => $chatId,
                'text' => $message,
                'disable_web_page_preview' => true,
            ]);

        if (! $response->successful() || $response->json('ok') !== true) {
            throw new RuntimeException($this->errorMessage($response));
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    private function errorMessage(Response $response): string
    {
        $description = $response->json('description');

        if (is_string($description) && $description !== '') {
            return 'O Telegram recusou a mensagem: '.$description;
        }

        return 'Falha ao enviar mensagem para o Telegram (HTTP '.$response->status().').';
    }
}
