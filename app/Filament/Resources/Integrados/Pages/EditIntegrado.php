<?php

namespace App\Filament\Resources\Integrados\Pages;

use App\Filament\Resources\Integrados\IntegradoResource;
use App\Models\Integrado;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrado extends EditRecord
{
    protected static string $resource = IntegradoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function getGlobalSearchResultDetails(Integrado $record): array
    {
        return [
            'Localização' => $record->municipio . ' - ' . $record->estado,
            'KM Rota' => $record->km_rota . ' km'
        ];
    }
}
