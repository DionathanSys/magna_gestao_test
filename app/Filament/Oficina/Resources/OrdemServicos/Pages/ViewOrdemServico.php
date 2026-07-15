<?php

namespace App\Filament\Oficina\Resources\OrdemServicos\Pages;

use App\Filament\Oficina\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewOrdemServico extends ViewRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => Auth::user()->is_admin),
        ];
    }
}
