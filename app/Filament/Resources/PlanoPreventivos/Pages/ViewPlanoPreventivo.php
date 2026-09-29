<?php

namespace App\Filament\Resources\PlanoPreventivos\Pages;

use App\Filament\Resources\PlanoPreventivos\PlanoPreventivoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlanoPreventivo extends ViewRecord
{
    protected static string $resource = PlanoPreventivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
