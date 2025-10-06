<?php

namespace App\Filament\Resources\Inspecaos\Pages;

use App\Filament\Resources\Inspecaos\InspecaoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInspecao extends EditRecord
{
    protected static string $resource = InspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
