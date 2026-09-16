<?php

namespace App\Filament\Resources\PlanoPreventivos\Pages;

use App\Filament\Resources\PlanoPreventivos\PlanoPreventivoResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlanoPreventivo extends EditRecord
{
    protected static string $resource = PlanoPreventivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            PlanoPreventivoResource::deleteAction(),
        ];
    }
}
