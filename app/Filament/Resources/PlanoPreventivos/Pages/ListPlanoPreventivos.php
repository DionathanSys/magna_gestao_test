<?php

namespace App\Filament\Resources\PlanoPreventivos\Pages;

use App\Filament\Resources\PlanoPreventivos\PlanoPreventivoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlanoPreventivos extends ListRecords
{
    protected static string $resource = PlanoPreventivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo plano preventivo'),
        ];
    }
}
