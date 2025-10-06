<?php

namespace App\Filament\Resources\ItemInspecaos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ItemInspecaoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('inspecao_id')
                    ->numeric(),
                TextEntry::make('inspecionavel_type'),
                TextEntry::make('inspecionavel_id')
                    ->numeric(),
                TextEntry::make('observacao'),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
