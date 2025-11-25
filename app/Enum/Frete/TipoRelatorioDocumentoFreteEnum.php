<?php

namespace App\Enum\Frete;

use App\Imports\DocumentoFreteImport;
use App\Services\Import\Importers\ViagemEspelhoFreteImporter;

enum TipoRelatorioDocumentoFreteEnum: string
{
    case RELATORIO_SANKHYA_CTE              = 'RELATORIO SANKHYA CTE';
    case ESPELHO_FRETE_NFS_BRF              = 'ESPELHO FRETE NFS BRF';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }

    public function config(): array
    {
        return match ($this) {
            self::RELATORIO_SANKHYA_CTE => [
                'class_importer' => DocumentoFreteImport::class,
                'file_type' => 'xlsx',
            ],
            self::ESPELHO_FRETE_NFS_BRF => [
                'class_importer' => ViagemEspelhoFreteImporter::class,
                'file_type' => 'pdf',
            ],
        };
    }
}
