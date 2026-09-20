<?php

namespace App\Domain\Automation\Data;

final readonly class AutomationImportPageResult
{
    public function __construct(
        public int $recordsReceived,
        public int $recordsCreated,
        public int $recordsUpdated,
        public int $recordsIgnored,
        public array $errors = [],
    ) {}
}
