<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Auth;

class EditResultadoPeriodo extends EditRecord
{
    protected static string $resource = ResultadoPeriodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => Auth::user()->is_admin),
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
