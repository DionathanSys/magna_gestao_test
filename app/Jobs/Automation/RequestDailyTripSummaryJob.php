<?php

namespace App\Jobs\Automation;

use App\Domain\Automation\Actions\RequestAutomationJob;
use App\Domain\Automation\Data\AutomationJobRequest;
use App\Enum\Automation\AutomationJobSource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RequestDailyTripSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(RequestAutomationJob $requestAutomationJob): void
    {
        $timezone = (string) config('automation.schedules.daily_trip_summary.timezone', config('app.timezone'));
        $daysOffset = max(0, (int) config('automation.schedules.daily_trip_summary.days_offset', 1));
        $referenceDate = CarbonImmutable::now($timezone)->subDays($daysOffset)->toDateString();
        $idempotencyKey = "schedule:daily_trip_summary:{$referenceDate}";

        $requestAutomationJob->handle(new AutomationJobRequest(
            reportKey: 'daily_trip_summary',
            parameters: [
                'date' => $referenceDate,
            ],
            source: AutomationJobSource::SCHEDULED,
            idempotencyKey: $idempotencyKey,
            metadata: [
                'schedule' => 'daily_trip_summary',
                'reference_date' => $referenceDate,
                'timezone' => $timezone,
            ],
        ));
    }
}
