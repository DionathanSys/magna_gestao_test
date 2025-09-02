<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\{Models, Enum, Services};
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class VincularPlanoPreventivoAction
{
    public static function make(?int $ordemServicoId = null, ?int $veiculoId = null): Action
    {
        return Action::make('vincular_plano_preventivo')
            ->label('Manutenção Preventiva')
            ->icon('heroicon-o-clipboard-document-list')
            ->modal()
            ->modalHeading('Vincular Plano Preventivo')
            ->modalDescription('Selecione um plano preventivo')
            ->modalIcon('heroicon-o-document-plus')
            ->modalWidth(Width::Large)
            ->modalAlignment(Alignment::Center)
            ->extraModalFooterActions(fn(Action $action): array => [
                $action->makeModalSubmitAction('vincularOutro', arguments: ['another' => true]),
            ])
            ->modalSubmitActionLabel('Vincular')
            ->schema(fn(Schema $form) => $form
                ->schema([
                    \Filament\Forms\Components\Select::make('plano_preventivo_id')
                        ->label('Plano Preventivo')
                        ->options(
                            Models\Veiculo::find($veiculoId)
                                ->planoPreventivo()
                                ->pluck('descricao', 'plano_preventivo_id')
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                ]))
            ->action(function (
                Action $action,
                Schema $form,
                Models\OrdemServico $record,
                array $data,
                array $arguments
            ) {
                Services\OrdemServico\ManutencaoPreventivaService::associarPlanoPreventivo($record, $data['plano_preventivo_id']);
                if ($arguments['another'] ?? false) {
                    $form->fill();
                    $action->halt();
                }
                return;
            })
            ->color('primary')
            ->icon('heroicon-o-wrench')
            ->successRedirectUrl(fn (Model $record): string => OrdemServicoResource::getUrl('custom', [
                        'record' => $record,
                    ]));
    }
}
