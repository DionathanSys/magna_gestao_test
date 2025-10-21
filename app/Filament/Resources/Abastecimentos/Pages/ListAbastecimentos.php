<?php

namespace App\Filament\Resources\Abastecimentos\Pages;

use App\Filament\Resources\Abastecimentos\AbastecimentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAbastecimentos extends ListRecords
{
    protected static string $resource = AbastecimentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
