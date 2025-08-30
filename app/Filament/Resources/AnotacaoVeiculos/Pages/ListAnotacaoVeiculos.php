<?php

namespace App\Filament\Resources\AnotacaoVeiculos\Pages;

use App\Filament\Resources\AnotacaoVeiculos\AnotacaoVeiculoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnotacaoVeiculos extends ListRecords
{
    protected static string $resource = AnotacaoVeiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
