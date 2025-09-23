<?php

namespace App\Services\Import;

use App\Services\Import\Importers\ViagemImporter;

class ViagemImportService extends BaseImportService
{
    public function importarViagens(string $filePath, array $options = []): array
    {
        ds('Iniciando importação de viagens')->label(__METHOD__)->blue();
        $importer = app(ViagemImporter::class);
        return $this->import($filePath, $importer, $options);
    }
}
