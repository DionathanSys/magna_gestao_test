<?php

namespace App\Filament\Resources\PneuMedidas\Pages;

use App\Filament\Resources\PneuMedidas\PneuMedidaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePneuMedidas extends ManageRecords
{
    protected static string $resource = PneuMedidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova Medida de Pneu'),
        ];
    }
}
