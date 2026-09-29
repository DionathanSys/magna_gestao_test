<?php

namespace Tests\Unit;

use App\Jobs\SendTelegramAlertJob;
use App\Models\TelegramAlert;
use App\Models\User;
use App\Services\TelegramMessageFormatter;
use App\Services\TelegramService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SendTelegramAlertJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('telegram_chat_id', 64)->nullable();
            $table->timestamps();
        });

        $alertsMigration = require database_path('migrations/2026_09_29_120000_create_telegram_alerts_table.php');
        $alertsMigration->up();

        $recipientsMigration = require database_path('migrations/2026_09_29_120100_create_telegram_alert_recipients_table.php');
        $recipientsMigration->up();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('telegram_alert_recipients');
        Schema::dropIfExists('telegram_alerts');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_it_sends_the_message_and_file_as_two_telegram_messages(): void
    {
        config([
            'services.telegram.enabled' => true,
            'services.telegram.bot_token' => 'test-token',
        ]);

        $user = User::create([
            'name' => 'Destinatário',
            'email' => 'destinatario@example.com',
            'is_active' => true,
            'telegram_chat_id' => '123456789',
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('telegram/alerts/relatorio.pdf', 'conteudo');

        Http::fake([
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 10],
            ], 200),
            'https://api.telegram.org/*/sendDocument' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 11],
            ], 200),
        ]);

        $alert = TelegramAlert::create([
            'message_html' => '<p><strong>Alerta:</strong> documento disponível.</p>',
            'telegram_options' => [
                'disable_web_page_preview' => true,
                'disable_notification' => false,
                'protect_content' => false,
            ],
            'file_path' => 'telegram/alerts/relatorio.pdf',
            'file_disk' => 'local',
        ]);
        $alert->recipients()->create([
            'user_id' => $user->id,
            'chat_id' => $user->telegram_chat_id,
        ]);

        (new SendTelegramAlertJob($alert->id))->handle(
            app(TelegramService::class),
            app(TelegramMessageFormatter::class),
        );

        $alert->refresh();
        $recipient = $alert->recipients()->firstOrFail();

        $this->assertSame('sent', $alert->status);
        $this->assertSame('sent', $recipient->status);
        $this->assertSame('10', $recipient->telegram_message_id);
        $this->assertSame('11', $recipient->telegram_document_message_id);
        Storage::disk('local')->assertMissing('telegram/alerts/relatorio.pdf');
        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/sendMessage')
            && $request['parse_mode'] === 'HTML'
            && $request['text'] === '<b>Alerta:</b> documento disponível.');
    }
}
