<?php

namespace App\Services\Import;

use App\Services\Import\Importers\EstoqueListagemImporter;
use App\Services\Import\Importers\EstoqueMovimentacaoDiariaImporter;

class EstoqueImportService extends BaseImportService
{
    public function importarListagem(string $filePath, array $options = []): array
    {
        return $this->import($filePath, app(EstoqueListagemImporter::class), $options);
    }

    public function importarMovimentacaoDiaria(string $filePath, array $options = []): array
    {
        return $this->import($filePath, app(EstoqueMovimentacaoDiariaImporter::class), $options);
    }
}
