<?php

namespace App\Filament\Resources\Lembretes\Pages;

use App\Filament\Resources\Lembretes\LembreteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLembrete extends EditRecord
{
    protected static string $resource = LembreteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
