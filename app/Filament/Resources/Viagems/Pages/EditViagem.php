<?php

namespace App\Filament\Resources\Viagems\Pages;

use App\Filament\Resources\Viagems\ViagemResource;
use App\Models\Viagem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditViagem extends EditRecord
{
    protected static string $resource = ViagemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function getGlobalSearchResultDetails(Viagem $record): array
    {
        return [
            'Localização' => $record->municipio . ' - ' . $record->estado,
            'KM Rota' => $record->km_rota . ' km'
        ];
    }
}
