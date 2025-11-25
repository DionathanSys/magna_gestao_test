<?php

namespace App\Services\Import;

use App\Services\Import\Importers\DocumentoFreteNutrepampaImporter;
use App\Services\Import\Importers\ViagemImporter;
use Illuminate\Support\Facades\Log;

class DocumentoFreteImportService extends BaseImportService
{
    public function importarDocumentosNutrepampa(string $filePath, array $options = []): array
    {
        $importer = app(DocumentoFreteNutrepampaImporter::class);
        return $this->import($filePath, $importer, $options);
    }
}
