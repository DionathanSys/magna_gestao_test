<?php

namespace App\Filament\Resources\Servicos\Pages;

use App\Filament\Resources\Servicos\ServicoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewServico extends ViewRecord
{
    protected static string $resource = ServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
