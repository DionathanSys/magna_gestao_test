<?php

namespace App\Domain\Automation\Data;

use App\Enum\Automation\AutomationJobSource;

final readonly class AutomationJobRequest
{
    public function __construct(
        public string $reportKey,
        public array $parameters = [],
        public AutomationJobSource $source = AutomationJobSource::MANUAL,
        public ?int $requestedByUserId = null,
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {}
}
