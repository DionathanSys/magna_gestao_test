<?php

namespace App\Filament\Resources\HistoricoQuilometragems\Pages;

use App\Filament\Resources\HistoricoQuilometragems\HistoricoQuilometragemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHistoricoQuilometragems extends ManageRecords
{
    protected static string $resource = HistoricoQuilometragemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
