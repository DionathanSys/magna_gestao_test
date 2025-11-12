<?php

namespace App\Services\Import;

use App\Services\Import\Importers\ViagemImporter;
use Illuminate\Support\Facades\Log;

class ViagemImportService extends BaseImportService
{
    public function importarViagens(string $filePath, array $options = [], array $additionalData = []): array
    {
        $importer = app(ViagemImporter::class);
        return $this->import($filePath, $importer, $options, $additionalData);
    }
}
