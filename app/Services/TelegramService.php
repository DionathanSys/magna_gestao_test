<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TelegramService
{
    public function isEnabled(): bool
    {
        return (bool) safe_db_config(
            'config-telegram.enabled',
            config('services.telegram.enabled', false),
        );
    }

    public function areAgendamentoRemindersEnabled(): bool
    {
        return (bool) safe_db_config(
            'config-telegram.agendamento_reminders_enabled',
            config('services.telegram.agendamento_reminders_enabled', true),
        );
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled() && filled($this->botToken());
    }

    public function sendMessage(string $chatId, string $message, array $options = []): array
    {
        $this->validateCanSend($chatId);

        $response = $this->client()
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post("/bot{$this->botToken()}/sendMessage", array_merge([
                'chat_id' => trim($chatId),
                'text' => $message,
                'disable_web_page_preview' => true,
            ], $options));

        return $this->ensureSuccessful($response, 'mensagem');
    }

    public function sendDocument(
        string $chatId,
        string $path,
        string $disk = 'local',
        array $options = [],
    ): array {
        $this->validateCanSend($chatId);

        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            throw new RuntimeException('O arquivo do alerta não pôde ser lido.');
        }

        $response = $this->client()
            ->acceptJson()
            ->timeout(60)
            ->attach('document', $storage->get($path), basename($path))
            ->post("/bot{$this->botToken()}/sendDocument", array_merge([
                'chat_id' => trim($chatId),
            ], $options));

        return $this->ensureSuccessful($response, 'arquivo');
    }

    private function validateCanSend(string $chatId): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('A integração com o Telegram está desabilitada.');
        }

        if (blank($this->botToken())) {
            throw new RuntimeException('O token do bot do Telegram não está configurado.');
        }

        if (blank(trim($chatId))) {
            throw new RuntimeException('O chat ID do Telegram não foi informado.');
        }
    }

    private function client()
    {
        return Http::baseUrl(rtrim((string) config('services.telegram.api_url'), '/'));
    }

    private function ensureSuccessful(Response $response, string $contentType): array
    {
        if ($response->failed() || $response->json('ok') !== true) {
            $description = (string) ($response->json('description') ?: 'Resposta inválida da API do Telegram.');

            Log::warning("Falha ao enviar {$contentType} pelo Telegram", [
                'status' => $response->status(),
                'description' => $description,
            ]);

            throw new RuntimeException($description);
        }

        return (array) $response->json();
    }

    private function botToken(): string
    {
        return trim((string) safe_db_config(
            'config-telegram.bot_token',
            config('services.telegram.bot_token'),
        ));
    }
}
