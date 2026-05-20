<?php

namespace App\Filament\Resources\PneuInspecoes\Pages;

use App\Filament\Resources\PneuInspecoes\PneuInspecaoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPneuInspecao extends EditRecord
{
    protected static string $resource = PneuInspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
