<?php

namespace App\Filament\Resources\ImportLogs\Pages;

use App\Filament\Resources\ImportLogs\ImportLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateImportLog extends CreateRecord
{
    protected static string $resource = ImportLogResource::class;
}
