<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewResultadoPeriodo extends ViewRecord
{
    protected static string $resource = ResultadoPeriodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
