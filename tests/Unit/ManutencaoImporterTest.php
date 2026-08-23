<?php

namespace Tests\Unit;

use App\Services\Import\Importers\ManutencaoImporter;
use App\Services\Manutencao\ManutencaoImportSyncService;
use PHPUnit\Framework\TestCase;

class ManutencaoImporterTest extends TestCase
{
    public function test_it_parses_negotiation_dates_from_the_maintenance_export_formats(): void
    {
        $importer = new ManutencaoImporter($this->createStub(ManutencaoImportSyncService::class));
        $method = new \ReflectionMethod($importer, 'parseDataNegociacao');

        $dataFromErp = $method->invoke($importer, '8/18/2026');
        $dataFromBrazilianReport = $method->invoke($importer, '18/08/2026');

        $this->assertSame('2026-08-18', $dataFromErp->toDateString());
        $this->assertSame('2026-08-18', $dataFromBrazilianReport->toDateString());
    }
}
