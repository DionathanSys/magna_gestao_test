<?php

namespace App\Filament\Resources\AnotacaoVeiculos\Pages;

use App\Filament\Resources\AnotacaoVeiculos\AnotacaoVeiculoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAnotacaoVeiculo extends EditRecord
{
    protected static string $resource = AnotacaoVeiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
