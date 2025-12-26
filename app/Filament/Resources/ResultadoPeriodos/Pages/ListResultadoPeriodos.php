<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use App\Filament\Resources\ResultadoPeriodos\Widgets\ResultadoPeriodoStats;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListResultadoPeriodos extends ListRecords
{
    protected static string $resource = ResultadoPeriodoResource::class;

    use ExposesTableToWidgets;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->preserveFormDataWhenCreatingAnother(['veiculo_id', 'tipo_veiculo', 'data_inicio', 'data_fim', 'status']),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResultadoPeriodoStats::class,
        ];
    }
}
