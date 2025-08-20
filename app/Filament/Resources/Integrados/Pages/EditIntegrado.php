<?php

namespace App\Filament\Resources\Integrados\Pages;

use App\Filament\Resources\Integrados\IntegradoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrado extends EditRecord
{
    protected static string $resource = IntegradoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
