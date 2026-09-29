<?php

namespace App\Domain\Automation\Contracts;

use App\Domain\Automation\Data\AutomationImportPageResult;
use App\Models\AutomationJob;

interface AutomationResultImporter
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $meta
     */
    public function importPage(
        AutomationJob $job,
        array $items,
        array $meta,
    ): AutomationImportPageResult;
}
