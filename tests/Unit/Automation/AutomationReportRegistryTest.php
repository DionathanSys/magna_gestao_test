<?php

namespace Tests\Unit\Automation;

use App\Domain\Automation\AutomationReportRegistry;
use Tests\TestCase;

class AutomationReportRegistryTest extends TestCase
{
    public function test_resolves_the_daily_trip_summary_definition(): void
    {
        $definition = app(AutomationReportRegistry::class)->get('daily_trip_summary');

        $this->assertSame('daily_trip_summary', $definition->collector);
        $this->assertSame('1.0.0', $definition->collectorVersion);
        $this->assertContains('numero_viagem', $definition->fields);
        $this->assertContains('motoristas', $definition->fields);
    }
}
