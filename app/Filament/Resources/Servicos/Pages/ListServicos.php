<?php

namespace App\Filament\Resources\Servicos\Pages;

use App\Filament\Resources\Servicos\ServicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServicos extends ListRecords
{
    protected static string $resource = ServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
