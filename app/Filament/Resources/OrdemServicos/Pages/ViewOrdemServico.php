<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrdemServico extends ViewRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
