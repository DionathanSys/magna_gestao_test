<?php

namespace App\Filament\Resources\PneuModelos\Pages;

use App\Filament\Resources\PneuModelos\PneuModeloResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePneuModelos extends ManageRecords
{
    protected static string $resource = PneuModeloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo Modelo de Pneu'),
        ];
    }
}
