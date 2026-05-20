<?php

namespace App\Filament\Resources\PneuInspecoes\Pages;

use App\Filament\Resources\PneuInspecoes\PneuInspecaoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPneuInspecao extends ViewRecord
{
    protected static string $resource = PneuInspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
