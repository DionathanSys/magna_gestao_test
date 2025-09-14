<?php

namespace App\Filament\Resources\HistoricoMovimentoPneus\Pages;

use App\Filament\Resources\HistoricoMovimentoPneus\HistoricoMovimentoPneuResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHistoricoMovimentoPneu extends EditRecord
{
    protected static string $resource = HistoricoMovimentoPneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
