<?php

namespace App\Filament\Resources\Abastecimentos\Pages;

use App\Filament\Resources\Abastecimentos\AbastecimentoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAbastecimento extends EditRecord
{
    protected static string $resource = AbastecimentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
