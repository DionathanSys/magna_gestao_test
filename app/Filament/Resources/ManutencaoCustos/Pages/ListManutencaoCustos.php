<?php

namespace App\Filament\Resources\ManutencaoCustos\Pages;

use App\Filament\Resources\ManutencaoCustos\ManutencaoCustoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManutencaoCustos extends ListRecords
{
    protected static string $resource = ManutencaoCustoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->preserveFormDataWhenCreatingAnother(['data_inicio', 'data_fim']),
        ];
    }
}
