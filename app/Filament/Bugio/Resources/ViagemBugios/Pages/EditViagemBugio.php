<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditViagemBugio extends EditRecord
{
    protected static string $resource = ViagemBugioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
