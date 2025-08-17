<?php

namespace App\Filament\Resources\Viagems\Pages;

use App\Filament\Resources\Viagems\ViagemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListViagems extends ListRecords
{
    protected static string $resource = ViagemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
