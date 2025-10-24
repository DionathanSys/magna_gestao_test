<?php

namespace App\Filament\Resources\TipoVeiculos\Pages;

use App\Filament\Resources\TipoVeiculos\TipoVeiculoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTipoVeiculos extends ManageRecords
{
    protected static string $resource = TipoVeiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
