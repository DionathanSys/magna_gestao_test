<?php

namespace App\Filament\Resources\MapasPneu\Pages;

use App\Filament\Resources\MapasPneu\MapaPneuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMapasPneu extends ListRecords
{
    protected static string $resource = MapaPneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo Mapa de Pneu'),
        ];
    }
}
