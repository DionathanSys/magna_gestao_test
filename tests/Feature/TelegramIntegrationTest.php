<?php

namespace Tests\Feature;

use App\Enum\Lembrete\StatusLembreteEnum;
use App\Jobs\Telegram\EnviarLembreteTelegram;
use App\Jobs\Telegram\EnviarMensagemTelegram;
use App\Models\Lembrete;
use App\Models\User;
use App\Services\NotificacaoService;
use App\Services\Telegram\TelegramService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase as ApplicationTestCase;

class TelegramIntegrationTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.enabled' => true,
            'services.telegram.bot_token' => '123456:test-token',
            'services.telegram.api_url' => 'https://api.telegram.org',
        ]);

        Schema::dropIfExists('lembretes');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('telegram_chat_id')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('lembretes', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo')->default('lembrete');
            $table->string('titulo');
            $table->text('mensagem');
            $table->foreignId('user_id');
            $table->timestamp('scheduled_at');
            $table->string('status')->default('pendente');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('notifiable_type');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lembretes');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_sends_a_message_through_the_telegram_bot_api(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 10],
            ]),
        ]);

        $response = app(TelegramService::class)->sendMessage('-100123', 'Mensagem de teste');

        $this->assertTrue($response['ok']);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.telegram.org/bot123456:test-token/sendMessage'
                && $request['chat_id'] === '-100123'
                && $request['text'] === 'Mensagem de teste';
        });
    }

    public function test_processes_only_due_reminders_and_claims_them_once(): void
    {
        Queue::fake();

        $user = User::query()->create([
            'name' => 'Usuário Telegram',
            'email' => 'telegram@example.com',
            'telegram_chat_id' => '-100123',
            'password' => 'password',
        ]);

        $due = Lembrete::query()->create([
            'titulo' => 'Alerta de teste',
            'mensagem' => 'Verificar operação.',
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
        ]);

        Lembrete::query()->create([
            'titulo' => 'Ainda não',
            'mensagem' => 'Não deve ser enviado.',
            'user_id' => $user->id,
            'scheduled_at' => now()->addMinute(),
        ]);

        $exitCode = Artisan::call('telegram:processar-lembretes');

        $this->assertSame(0, $exitCode);
        $this->assertSame(StatusLembreteEnum::ENVIANDO, $due->fresh()->status);
        Queue::assertPushed(EnviarLembreteTelegram::class, function (EnviarLembreteTelegram $job) use ($due): bool {
            return $job->lembreteId === $due->id;
        });
        $this->assertSame(1, Lembrete::query()->where('status', StatusLembreteEnum::ENVIANDO->value)->count());
    }

    public function test_marks_a_reminder_as_sent_after_the_api_call(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $user = User::query()->create([
            'name' => 'Usuário Telegram',
            'email' => 'telegram@example.com',
            'telegram_chat_id' => '-100123',
            'password' => 'password',
        ]);

        $lembrete = Lembrete::query()->create([
            'titulo' => 'Alerta de teste',
            'mensagem' => 'Mensagem enviada.',
            'user_id' => $user->id,
            'scheduled_at' => now()->subMinute(),
            'status' => StatusLembreteEnum::ENVIANDO->value,
        ]);

        (new EnviarLembreteTelegram($lembrete->id))->handle(app(TelegramService::class));

        $lembrete->refresh();

        $this->assertSame(StatusLembreteEnum::ENVIADO, $lembrete->status);
        $this->assertNotNull($lembrete->sent_at);
        $this->assertNull($lembrete->error_message);
    }

    public function test_persisted_notifications_are_queued_for_users_with_a_chat_id(): void
    {
        Queue::fake();

        $user = User::query()->create([
            'name' => 'Usuário Telegram',
            'email' => 'telegram@example.com',
            'telegram_chat_id' => '-100123',
            'password' => 'password',
        ]);

        (new NotificacaoService('warning', 'Alerta', 'Mensagem persistida.'))->sendToDataBase($user);

        Queue::assertPushed(EnviarMensagemTelegram::class, function (EnviarMensagemTelegram $job): bool {
            return $job->chatId === '-100123'
                && $job->message === "Alerta\n\nMensagem persistida.";
        });
    }
}
