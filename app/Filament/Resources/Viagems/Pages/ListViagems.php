<?php

namespace App\Filament\Resources\Viagems\Pages;

use App\Filament\Resources\Viagems\Actions\ExportarViagensPorPeriodoAction;
use App\Filament\Resources\Viagems\ViagemResource;
use Filament\Resources\Pages\ListRecords;

class ListViagems extends ListRecords
{
    protected static string $resource = ViagemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportarViagensPorPeriodoAction::make(),
        ];
    }
}
