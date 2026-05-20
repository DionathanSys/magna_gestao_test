<?php

namespace App\Filament\Resources\PneuMarcas\Pages;

use App\Filament\Resources\PneuMarcas\PneuMarcaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePneuMarcas extends ManageRecords
{
    protected static string $resource = PneuMarcaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova Marca de Pneu'),
        ];
    }
}
