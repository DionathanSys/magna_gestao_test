<?php

namespace Tests\Unit\Automation;

use App\Domain\Automation\Actions\RequestAutomationJob;
use App\Domain\Automation\Data\AutomationJobRequest;
use App\Jobs\Automation\RequestDailyTripSummaryJob;
use App\Models\AutomationJob;
use Carbon\CarbonImmutable;
use Mockery;
use Tests\TestCase;

class RequestDailyTripSummaryJobTest extends TestCase
{
    public function test_requests_the_previous_day(): void
    {
        $now = CarbonImmutable::create(2026, 10, 9, 5, 0, 0, 'America/Sao_Paulo');
        CarbonImmutable::setTestNow($now);

        try {
            $requestAutomationJob = Mockery::mock(RequestAutomationJob::class);
            $requestAutomationJob
                ->shouldReceive('handle')
                ->once()
                ->with(Mockery::on(function (AutomationJobRequest $request): bool {
                    $this->assertSame('daily_trip_summary', $request->reportKey);
                    $this->assertSame([
                        'from' => '2026-10-08',
                        'to' => '2026-10-08',
                    ], $request->parameters);
                    $this->assertSame('schedule:daily_trip_summary:2026-10-08:2026-10-08', $request->idempotencyKey);

                    return true;
                }))
                ->andReturn(new AutomationJob);

            (new RequestDailyTripSummaryJob)->handle($requestAutomationJob);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
