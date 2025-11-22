<?php

namespace App\Filament\Resources\ManutencaoCustos\Pages;

use App\Filament\Resources\ManutencaoCustos\ManutencaoCustoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditManutencaoCusto extends EditRecord
{
    protected static string $resource = ManutencaoCustoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
