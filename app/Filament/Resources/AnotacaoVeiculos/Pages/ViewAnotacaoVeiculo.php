<?php

namespace App\Filament\Resources\AnotacaoVeiculos\Pages;

use App\Filament\Resources\AnotacaoVeiculos\AnotacaoVeiculoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAnotacaoVeiculo extends ViewRecord
{
    protected static string $resource = AnotacaoVeiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
