<?php

namespace App\Filament\Resources\Integrados\Pages;

use App\Filament\Resources\Integrados\IntegradoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrados extends ListRecords
{
    protected static string $resource = IntegradoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
