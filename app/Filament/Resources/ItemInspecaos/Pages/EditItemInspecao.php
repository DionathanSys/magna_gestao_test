<?php

namespace App\Filament\Resources\ItemInspecaos\Pages;

use App\Filament\Resources\ItemInspecaos\ItemInspecaoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditItemInspecao extends EditRecord
{
    protected static string $resource = ItemInspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
