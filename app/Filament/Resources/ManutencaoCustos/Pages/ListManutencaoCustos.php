<?php

namespace App\Filament\Resources\ManutencaoCustos\Pages;

use App\Filament\Resources\ManutencaoCustos\ManutencaoCustoResource;
use App\Filament\Resources\ManutencaoLancamentos\Actions\ImportarManutencaoAction;
use Filament\Resources\Pages\ListRecords;

class ListManutencaoCustos extends ListRecords
{
    protected static string $resource = ManutencaoCustoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportarManutencaoAction::make(),
        ];
    }
}
