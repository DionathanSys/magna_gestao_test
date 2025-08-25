<?php

namespace App\Filament\Resources\DocumentoFretes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DocumentoFreteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('veiculo.id'),
                TextEntry::make('integrado.id'),
                TextEntry::make('numero_documento'),
                TextEntry::make('documento_transporte'),
                TextEntry::make('tipo_documento'),
                TextEntry::make('data_emissao')
                    ->date(),
                TextEntry::make('valor_total')
                    ->numeric(),
                TextEntry::make('valor_icms')
                    ->numeric(),
                TextEntry::make('municipio'),
                TextEntry::make('estado'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
