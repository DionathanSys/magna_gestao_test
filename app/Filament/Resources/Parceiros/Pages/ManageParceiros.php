<?php

namespace App\Filament\Resources\Parceiros\Pages;

use App\Filament\Resources\Parceiros\ParceiroResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageParceiros extends ManageRecords
{
    protected static string $resource = ParceiroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
