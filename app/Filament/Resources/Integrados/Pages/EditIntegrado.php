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

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Geral';
    }


}
