<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TelegramService
{
    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $chatId, string $message, array $options = []): array
    {
        $token = trim((string) config('services.telegram.bot_token'));

        if (! (bool) config('services.telegram.enabled', false) || $token === '') {
            throw new RuntimeException('A integração com o Telegram não está configurada.');
        }

        if (trim($chatId) === '') {
            throw new RuntimeException('O destinatário do Telegram não foi informado.');
        }

        $response = $this->client()
            ->post('/bot'.$token.'/sendMessage', array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'disable_web_page_preview' => true,
            ], $options));

        return $this->ensureSuccessful($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendDocument(
        string $chatId,
        string $path,
        string $disk = 'local',
        array $options = [],
    ): array {
        $token = trim((string) config('services.telegram.bot_token'));

        if (! (bool) config('services.telegram.enabled', false) || $token === '') {
            throw new RuntimeException('A integração com o Telegram não está configurada.');
        }

        if (trim($chatId) === '') {
            throw new RuntimeException('O destinatário do Telegram não foi informado.');
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            throw new RuntimeException('O arquivo do alerta não pôde ser lido.');
        }

        $response = $this->client()
            ->attach('document', $storage->get($path), basename($path))
            ->post('/bot'.$token.'/sendDocument', array_merge([
                'chat_id' => $chatId,
            ], $options));

        return $this->ensureSuccessful($response);
    }

    private function client()
    {
        return Http::baseUrl(rtrim((string) config('services.telegram.api_url'), '/'))
            ->timeout((int) config('services.telegram.timeout_seconds', 10))
            ->connectTimeout((int) config('services.telegram.connect_timeout_seconds', 5))
            ->retry(
                max(1, (int) config('services.telegram.retry_times', 3)),
                max(0, (int) config('services.telegram.retry_sleep_milliseconds', 250)),
                null,
                false,
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function ensureSuccessful(Response $response): array
    {
        if (! $response->successful() || $response->json('ok') !== true) {
            $description = $response->json('description');

            if (is_string($description) && $description !== '') {
                throw new RuntimeException('O Telegram recusou o envio: '.$description);
            }

            throw new RuntimeException('Falha ao enviar para o Telegram (HTTP '.$response->status().').');
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }
}
