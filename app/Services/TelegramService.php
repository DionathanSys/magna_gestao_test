<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function sendMessage(string $chatId, string $message): void
    {
        $token = $this->botToken();

        if (! $this->isEnabled()) {
            throw new RuntimeException('A integração com o Telegram está desabilitada.');
        }

        if (blank($token)) {
            throw new RuntimeException('O token do bot do Telegram não está configurado.');
        }

        if (blank(trim($chatId))) {
            throw new RuntimeException('O chat ID do Telegram não foi informado.');
        }

        $response = Http::baseUrl(rtrim((string) config('services.telegram.api_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post("/bot{$token}/sendMessage", [
                'chat_id' => trim($chatId),
                'text' => $message,
                'disable_web_page_preview' => true,
            ]);

        if ($response->failed() || $response->json('ok') !== true) {
            $description = (string) ($response->json('description') ?: 'Resposta inválida da API do Telegram.');

            Log::warning('Falha ao enviar mensagem pelo Telegram', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'description' => $description,
            ]);

            throw new RuntimeException($description);
        }
    }

    private function botToken(): string
    {
        return trim((string) safe_db_config(
            'config-telegram.bot_token',
            config('services.telegram.bot_token'),
        ));
    }
}
