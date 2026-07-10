<?php

namespace App\Filament\Resources\ManutencaoLancamentos\Pages;

use App\Filament\Resources\ManutencaoLancamentos\Actions\ImportarManutencaoAction;
use App\Filament\Resources\ManutencaoLancamentos\ManutencaoLancamentoResource;
use App\Models\ManutencaoLancamento;
use App\Services\Manutencao\ManutencaoLancamentoVinculoService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListManutencaoLancamentos extends ListRecords
{
    protected static string $resource = ManutencaoLancamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportarManutencaoAction::make(),
            Action::make('conciliar_pendentes')
                ->label('Conciliar pendentes')
                ->icon('heroicon-o-arrow-path')
                ->action(function (ManutencaoLancamentoVinculoService $service): void {
                    ManutencaoLancamento::query()
                        ->whereNull('ordem_servico_id')
                        ->where('dispensado_vinculo', false)
                        ->whereNotNull('nr_os_nf')
                        ->orderBy('id')
                        ->each(fn (ManutencaoLancamento $lancamento) => $service->conciliarAutomaticamente($lancamento));
                }),
        ];
    }
}
