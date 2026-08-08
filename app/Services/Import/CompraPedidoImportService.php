<?php

namespace App\Services\Import;

use App\Services\Import\Importers\CompraPedidoImporter;

class CompraPedidoImportService extends BaseImportService
{
    public function importarItens(string $filePath, array $options = []): array
    {
        return $this->import($filePath, app(CompraPedidoImporter::class), $options);
    }
}
