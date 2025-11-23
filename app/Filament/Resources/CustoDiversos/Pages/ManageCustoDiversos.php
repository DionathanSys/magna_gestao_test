<?php

namespace App\Filament\Resources\CustoDiversos\Pages;

use App\Filament\Resources\CustoDiversos\CustoDiversoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCustoDiversos extends ManageRecords
{
    protected static string $resource = CustoDiversoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
