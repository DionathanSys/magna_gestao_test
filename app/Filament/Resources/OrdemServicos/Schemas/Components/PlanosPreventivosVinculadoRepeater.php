<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\{Models, Enum, Services};
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PlanosPreventivosVinculadoRepeater
{
    public static function make(): Repeater
    {
        return Repeater::make('planoPreventivoVinculado')
            ->label('Planos Vinculados')
            ->disabled()
            ->relationship()
            ->columns(12)
            ->deletable(fn(): bool => Auth::user()->is_admin)
            ->addable(false)
            ->collapsible()
            ->schema([
                TextEntry::make('id')
                    ->label('ID')
                    ->columnSpan([
                        'default' => 6,
                        'md' => 2,
                        'lg' => 4,
                        'xl' => 2,
                    ]),
                TextEntry::make('planoPreventivo.id')
                    ->label('ID Plano')
                    ->columnSpan([
                        'default' => 6,
                        'md' => 2,
                        'lg' => 4,
                        'xl' => 2,
                    ]),
                TextEntry::make('planoPreventivo.descricao')
                    ->label('Plano Preventivo')
                    ->columnSpan([
                        'default' => 12,
                        'lg' => 6,
                    ])
                    ->columnStart(1),
                TextEntry::make('planoPreventivo.intervalo')
                    ->label('Intervalo')
                    ->columnSpan([
                        'default' => 12,
                        'lg' => 6,
                    ]),

            ])
            ->extraItemActions([
                Action::make('removerVinculo')
                    ->icon(Heroicon::Trash)
                    ->action(function (array $arguments, Repeater $component, $state, $record): void {
                        $planoOrdem = Models\PlanoManutencaoOrdemServico::find($state[$arguments['item']]['id']);
                        Services\OrdemServico\ManutencaoPreventivaService::desassociarPlanoPreventivo($planoOrdem);
                    })
                    ->successRedirectUrl(fn(Model $record): string => OrdemServicoResource::getUrl('custom', [
                        'record' => $record,
                    ])),
            ]);
    }
}
