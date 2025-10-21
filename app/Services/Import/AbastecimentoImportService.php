<?php

namespace App\Services\Import;

use App\Services\Import\Importers\AbastecimentoImporter;
use Illuminate\Support\Facades\Log;

class AbastecimentoImportService extends BaseImportService
{
    public function importarAbastecimentos(string $filePath, array $options = []): array
    {
        Log::info('Iniciando importação de abastecimentos', ['filePath' => $filePath, 'options' => $options]);
        
        $importer = app(AbastecimentoImporter::class);
        return $this->import($filePath, $importer, $options);
    }
}
