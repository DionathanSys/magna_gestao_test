<?php

namespace App\Filament\Resources\PneuLocais\Pages;

use App\Filament\Resources\PneuLocais\PneuLocalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePneuLocais extends ManageRecords
{
    protected static string $resource = PneuLocalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo Local de Pneu'),
        ];
    }
}
