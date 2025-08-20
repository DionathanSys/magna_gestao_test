<?php

namespace App\Filament\Resources\CargaViagems\Pages;

use App\Filament\Resources\CargaViagems\CargaViagemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCargaViagem extends EditRecord
{
    protected static string $resource = CargaViagemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
