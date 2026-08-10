<?php

namespace App\Filament\Resources\CompraOrdens\Pages;

use App\Filament\Resources\CompraOrdens\CompraOrdemResource;
use Filament\Resources\Pages\EditRecord;

class EditCompraOrdem extends EditRecord
{
    protected static string $resource = CompraOrdemResource::class;

    protected function afterSave(): void
    {
        $this->record->refresh()->atualizarAtendimento();
        $this->record->pedido->refresh()->atualizarAtendimento();
    }
}
