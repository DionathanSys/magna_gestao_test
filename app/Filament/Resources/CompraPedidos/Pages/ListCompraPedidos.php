<?php

namespace App\Filament\Resources\CompraPedidos\Pages;

use App\Filament\Resources\CompraPedidos\CompraPedidoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompraPedidos extends ListRecords
{
    protected static string $resource = CompraPedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
