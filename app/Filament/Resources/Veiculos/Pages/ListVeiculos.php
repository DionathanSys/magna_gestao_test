<?php

namespace App\Filament\Resources\Veiculos\Pages;

use App\Filament\Resources\Veiculos\VeiculoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVeiculos extends ListRecords
{
    protected static string $resource = VeiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
