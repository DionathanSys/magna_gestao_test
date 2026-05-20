<?php

namespace App\Filament\Resources\PneuInspecoes\Pages;

use App\Filament\Resources\PneuInspecoes\PneuInspecaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPneuInspecoes extends ListRecords
{
    protected static string $resource = PneuInspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova Inspeção de Pneu'),
        ];
    }
}
