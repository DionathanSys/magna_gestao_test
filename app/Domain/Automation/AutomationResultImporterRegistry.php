<?php

namespace App\Domain\Automation;

use App\Domain\Automation\Contracts\AutomationResultImporter;
use App\Domain\Automation\Importers\DailyTripSummaryImporter;
use InvalidArgumentException;

class AutomationResultImporterRegistry
{
    public function get(AutomationReportDefinition $definition): AutomationResultImporter
    {
        return match ($definition->key) {
            'daily_trip_summary' => app(DailyTripSummaryImporter::class),
            default => throw new InvalidArgumentException(
                "Importador nao configurado para o relatorio {$definition->key}."
            ),
        };
    }
}
