<?php

namespace App\Services\Import;

use App\Services\Import\Importers\ManutencaoImporter;

class ManutencaoImportService extends BaseImportService
{
    public function importarLancamentos(string $filePath, array $options = []): array
    {
        $importer = app(ManutencaoImporter::class);

        return $this->import($filePath, $importer, $options);
    }
}
