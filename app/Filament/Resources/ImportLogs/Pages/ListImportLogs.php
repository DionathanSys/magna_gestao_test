<?php

namespace App\Filament\Resources\ImportLogs\Pages;

use App\Filament\Resources\ImportLogs\ImportLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportLogs extends ListRecords
{
    protected static string $resource = ImportLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
