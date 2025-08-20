<?php

namespace App\Filament\Resources\CargaViagems\Pages;

use App\Filament\Resources\CargaViagems\CargaViagemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCargaViagems extends ListRecords
{
    protected static string $resource = CargaViagemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
