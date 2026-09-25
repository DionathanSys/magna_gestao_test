<?php

namespace Tests\Unit\Automation;

use App\Domain\Automation\AutomationReportRegistry;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AutomationReportRegistryTest extends TestCase
{
    public function test_resolves_the_daily_trip_summary_definition(): void
    {
        $definition = app(AutomationReportRegistry::class)->get('daily_trip_summary');

        $this->assertSame('daily_trip_summary', $definition->collector);
        $this->assertSame('1.0.0', $definition->collectorVersion);
        $this->assertSame('1.0', $definition->schemaVersion);
        $this->assertSame(['required', 'date_format:Y-m-d'], $definition->parameterRules['from']);
        $this->assertSame(['required', 'date_format:Y-m-d', 'after_or_equal:from'], $definition->parameterRules['to']);
        $this->assertContains('numero_viagem', $definition->fields);
        $this->assertContains('motoristas', $definition->fields);
    }

    public function test_accepts_an_inclusive_date_range_and_rejects_the_legacy_date_parameter(): void
    {
        $definition = app(AutomationReportRegistry::class)->get('daily_trip_summary');
        $registry = app(AutomationReportRegistry::class);

        $registry->validateParameters($definition, [
            'from' => '2026-09-19',
            'to' => '2026-09-21',
        ]);

        $this->expectException(ValidationException::class);

        $registry->validateParameters($definition, [
            'date' => '2026-09-19',
        ]);
    }
}
