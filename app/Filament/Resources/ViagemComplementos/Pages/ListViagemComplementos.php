<?php

namespace App\Filament\Resources\ViagemComplementos\Pages;

use App\Filament\Resources\ViagemComplementos\ViagemComplementoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListViagemComplementos extends ListRecords
{
    protected static string $resource = ViagemComplementoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
