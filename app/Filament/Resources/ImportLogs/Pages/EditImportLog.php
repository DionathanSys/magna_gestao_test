<?php

namespace App\Filament\Resources\ImportLogs\Pages;

use App\Filament\Resources\ImportLogs\ImportLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditImportLog extends EditRecord
{
    protected static string $resource = ImportLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
