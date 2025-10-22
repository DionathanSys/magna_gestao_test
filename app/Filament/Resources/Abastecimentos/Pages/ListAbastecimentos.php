<?php

namespace App\Filament\Resources\Abastecimentos\Pages;

use App\Filament\Resources\Abastecimentos\AbastecimentoResource;
use App\Filament\Resources\Abastecimentos\Widgets\ConsumoMedioDiesel;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListAbastecimentos extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = AbastecimentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConsumoMedioDiesel::class,
        ];
    }
}
