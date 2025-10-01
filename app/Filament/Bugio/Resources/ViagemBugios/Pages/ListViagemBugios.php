<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListViagemBugios extends ListRecords
{
    protected static string $resource = ViagemBugioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
