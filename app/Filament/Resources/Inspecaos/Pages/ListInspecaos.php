<?php

namespace App\Filament\Resources\Inspecaos\Pages;

use App\Filament\Resources\Inspecaos\InspecaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInspecaos extends ListRecords
{
    protected static string $resource = InspecaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
