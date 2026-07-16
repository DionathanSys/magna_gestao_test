<?php

namespace App\Filament\Resources\VeiculoDocumentos\Pages;

use App\Filament\Resources\VeiculoDocumentos\VeiculoDocumentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVeiculoDocumentos extends ListRecords
{
    protected static string $resource = VeiculoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo Documento'),
        ];
    }
}
