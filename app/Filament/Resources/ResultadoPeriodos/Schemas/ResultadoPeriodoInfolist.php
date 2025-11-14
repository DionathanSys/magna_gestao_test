<?php

namespace App\Filament\Resources\ResultadoPeriodos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ResultadoPeriodoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('veiculo_id')
                    ->numeric(),
                TextEntry::make('tipo_veiculo_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('data_inicio')
                    ->date(),
                TextEntry::make('data_fim')
                    ->date(),
                TextEntry::make('km_inicial')
                    ->numeric(),
                TextEntry::make('km_final')
                    ->numeric(),
                TextEntry::make('km_percorrido')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
