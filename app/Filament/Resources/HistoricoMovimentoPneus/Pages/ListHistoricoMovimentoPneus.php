<?php

namespace App\Filament\Resources\HistoricoMovimentoPneus\Pages;

use App\Filament\Resources\HistoricoMovimentoPneus\HistoricoMovimentoPneuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHistoricoMovimentoPneus extends ListRecords
{
    protected static string $resource = HistoricoMovimentoPneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
