<?php

namespace Tests\Feature;

use App\Models\ResultadoPeriodoCompartilhamento;
use App\Services\ResultadoPeriodo\ResultadoPeriodoDashboardShareService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResultadoPeriodoDashboardShareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('resultado_periodo_compartilhamentos', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->json('resultado_periodo_ids');
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->string('destinatario_nome');
            $table->string('destinatario_email')->nullable();
            $table->unsignedBigInteger('criado_por_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('resultado_periodo_compartilhamentos');

        parent::tearDown();
    }

    public function test_creates_a_signed_link_without_storing_the_raw_token(): void
    {
        $payload = app(ResultadoPeriodoDashboardShareService::class)->create(
            '2026-09-01',
            '2026-09-30',
            'Maria Silva',
            'maria@example.com',
            24,
            null,
        );
        $token = basename(parse_url($payload['url'], PHP_URL_PATH));
        $share = $payload['share']->fresh();

        $this->assertSame([], $share->resultado_periodo_ids);
        $this->assertSame('2026-09-01', $share->data_inicio->toDateString());
        $this->assertSame('2026-09-30', $share->data_fim->toDateString());
        $this->assertSame(hash('sha256', $token), $share->token_hash);
        $this->assertNotSame($token, $share->token_hash);
        $this->assertNotNull(parse_url($payload['url'], PHP_URL_QUERY));
        $this->assertNotNull(app(ResultadoPeriodoDashboardShareService::class)->resolve($token));
    }

    public function test_does_not_resolve_an_expired_link(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00'));
        $payload = app(ResultadoPeriodoDashboardShareService::class)->create(
            '2026-09-01',
            '2026-09-30',
            'Maria Silva',
            'maria@example.com',
            1,
            null,
        );
        $token = basename(parse_url($payload['url'], PHP_URL_PATH));

        Carbon::setTestNow(Carbon::parse('2026-09-11 11:01:00'));

        $this->assertNull(app(ResultadoPeriodoDashboardShareService::class)->resolve($token));
        $this->assertTrue(ResultadoPeriodoCompartilhamento::query()->exists());
    }

    public function test_rejects_a_dashboard_request_without_a_valid_signature(): void
    {
        $this->get('/resultado-periodo/dashboard/token-invalido')
            ->assertForbidden()
            ->assertSee('Link inválido ou expirado');
    }
}
