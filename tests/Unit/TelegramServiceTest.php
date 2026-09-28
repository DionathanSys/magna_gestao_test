<?php

namespace Tests\Unit;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    public function test_it_sends_a_message_to_the_telegram_bot_api(): void
    {
        config([
            'services.telegram.enabled' => true,
            'services.telegram.bot_token' => 'test-token',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        app(TelegramService::class)->sendMessage('123456789', 'Mensagem de teste');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '123456789'
                && $request['text'] === 'Mensagem de teste';
        });
    }

    public function test_it_throws_when_telegram_rejects_the_message(): void
    {
        config([
            'services.telegram.enabled' => true,
            'services.telegram.bot_token' => 'test-token',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => false,
                'description' => 'Chat not found',
            ], 400),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chat not found');

        app(TelegramService::class)->sendMessage('invalid-chat', 'Mensagem de teste');
    }
}
