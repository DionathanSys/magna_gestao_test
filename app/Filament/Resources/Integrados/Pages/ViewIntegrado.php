<?php

namespace App\Filament\Resources\Integrados\Pages;

use App\Filament\Resources\Integrados\IntegradoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIntegrado extends ViewRecord
{
    protected static string $resource = IntegradoResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
