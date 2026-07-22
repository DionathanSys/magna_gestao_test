<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Filament\Oficina\Resources\OrdemServicos\Tables\OrdemServicosTable as OficinaOrdemServicosTable;
use App\Filament\Resources\OrdemServicos\Actions\EncerrarOrdemServicoAction;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrdemServico extends EditRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            OficinaOrdemServicosTable::servicosAction(),
            OficinaOrdemServicosTable::iniciarAction(),
            OficinaOrdemServicosTable::encerrarAction(),
            ActionGroup::make([
                EncerrarOrdemServicoAction::make(),
                OficinaOrdemServicosTable::ajustarHorariosAction(),
                OficinaOrdemServicosTable::removerApontamentoAbertoAction(),
                OficinaOrdemServicosTable::relatorioAction(),
            ])
                ->label('Oficina')
                ->icon('heroicon-o-wrench-screwdriver')
                ->button(),
            DeleteAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Ordem';
    }
}
