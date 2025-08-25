<?php

namespace App\Enum\Frete;

use App\Imports\DocumentoFreteImport;

enum TipoRelatorioDocumentoFreteEnum: string
{
    case RELATORIO_SANKHYA_CTE = 'RELATORIO SANKHYA CTE';

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
        };
    }
}
