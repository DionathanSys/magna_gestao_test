<?php

namespace App\Filament\Resources\Servicos\Pages;

use App\Filament\Resources\Servicos\ServicoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServico extends EditRecord
{
    protected static string $resource = ServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
