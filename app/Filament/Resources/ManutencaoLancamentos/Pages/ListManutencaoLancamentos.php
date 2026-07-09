<?php

namespace App\Filament\Resources\ManutencaoLancamentos\Pages;

use App\Filament\Resources\ManutencaoLancamentos\Actions\ImportarManutencaoAction;
use App\Filament\Resources\ManutencaoLancamentos\ManutencaoLancamentoResource;
use Filament\Resources\Pages\ListRecords;

class ListManutencaoLancamentos extends ListRecords
{
    protected static string $resource = ManutencaoLancamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportarManutencaoAction::make(),
        ];
    }
}
