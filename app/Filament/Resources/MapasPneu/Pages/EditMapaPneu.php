<?php

namespace App\Filament\Resources\MapasPneu\Pages;

use App\Filament\Resources\MapasPneu\MapaPneuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMapaPneu extends EditRecord
{
    protected static string $resource = MapaPneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
