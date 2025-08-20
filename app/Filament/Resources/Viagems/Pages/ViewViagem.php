<?php

namespace App\Filament\Resources\Viagems\Pages;

use App\Filament\Resources\Viagems\ViagemResource;
use App\Models\Viagem;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewViagem extends ViewRecord
{
    protected static string $resource = ViagemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public static function getGlobalSearchResultDetails(Viagem $record): array
    {
        return [
            'Placa' => $record->veiculo->placa,
            'Dispersão' => ($record->km_dispersao ?? '-') . ' km - ' . ($record->dispersao_percentual ?? '-') . '%',
        ];
    }
}
