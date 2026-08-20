<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\ResultadoPeriodos\Actions\EncerrarResultadoAction;
use App\Filament\Resources\ResultadoPeriodos\Actions\ImportarRegistrosAction;
use App\Filament\Resources\ResultadoPeriodos\Actions\ReabrirResultadoAction;
use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditResultadoPeriodo extends EditRecord
{
    protected static string $resource = ResultadoPeriodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportarRegistrosAction::make(),
            EncerrarResultadoAction::make(),
            ReabrirResultadoAction::make(),
            Action::make('analise_veiculo')
                ->label('Análise do veículo')
                ->icon('heroicon-o-chart-bar-square')
                ->url(fn (): string => ResultadoPeriodoResource::getUrl('analise', ['record' => $this->record])),
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
