<?php

namespace App\Filament\Resources\Checklists\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ChecklistInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('veiculo_id')
                    ->numeric(),
                TextEntry::make('data_referencia')
                    ->date(),
                TextEntry::make('periodo')
                    ->date(),
                TextEntry::make('quilometragem')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
