<?php

namespace App\Jobs\Automation;

use App\Domain\Automation\Actions\RequestAutomationJob;
use App\Domain\Automation\Data\AutomationJobRequest;
use App\Enum\Automation\AutomationJobSource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RequestSascarTraveledDistanceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly string $scheduleSlot,
    ) {}

    public function handle(RequestAutomationJob $requestAutomationJob): void
    {
        $timezone = 'America/Sao_Paulo';
        $referenceDate = CarbonImmutable::now($timezone)->toDateString();
        $idempotencyKey = "schedule:sascar_traveled_distance:{$referenceDate}:{$this->scheduleSlot}";

        $requestAutomationJob->handle(new AutomationJobRequest(
            reportKey: 'sascar_traveled_distance',
            parameters: [
                'from' => $referenceDate,
                'to' => $referenceDate,
            ],
            source: AutomationJobSource::SCHEDULED,
            idempotencyKey: $idempotencyKey,
            metadata: [
                'schedule' => 'sascar_traveled_distance',
                'schedule_slot' => $this->scheduleSlot,
                'reference_date' => $referenceDate,
                'timezone' => $timezone,
            ],
        ));
    }
}
