<?php

namespace App\Filament\Resources\Pneus\Pages;

use App\Filament\Resources\Pneus\PneuResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPneu extends ViewRecord
{
    protected static string $resource = PneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
