<?php

namespace App\Filament\Resources\HistoricoMovimentoPneus\Pages;

use App\Filament\Resources\HistoricoMovimentoPneus\HistoricoMovimentoPneuResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHistoricoMovimentoPneu extends ViewRecord
{
    protected static string $resource = HistoricoMovimentoPneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
