<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrdemServico extends EditRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
