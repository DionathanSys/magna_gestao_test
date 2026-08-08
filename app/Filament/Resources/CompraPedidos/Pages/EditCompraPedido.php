<?php

namespace App\Filament\Resources\CompraPedidos\Pages;

use App\Filament\Resources\CompraPedidos\CompraPedidoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompraPedido extends EditRecord
{
    protected static string $resource = CompraPedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
