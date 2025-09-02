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
                    ->columnSpan(2),
                TextEntry::make('planoPreventivo.id')
                    ->label('ID Plano')
                    ->columnSpan(2),
                TextEntry::make('planoPreventivo.descricao')
                    ->label('Plano Preventivo')
                    ->columnSpan(12),
                TextEntry::make('planoPreventivo.periodicidade')
                    ->label('Periodicidade')
                    ->columnSpan(12),

            ])
            ->extraItemActions([
                // Action::make('vincularAgendamento')
                //     ->icon(Heroicon::Link)
                //     ->action(function (array $arguments, Repeater $component, $state,$record): void {
                //         $agendamento = Models\Agendamento::find($state[$arguments['item']]['id']);
                //         $service = new Services\Agendamento\AgendamentoService();
                //         $service->vincularEmOrdemServico($agendamento);
                //         if($service->hasError()) {
                //             notify::error($service->getMessage());
                //             return;
                //         }
                //         notify::success('Agendamento vinculado com sucesso!');
                //     })
                //     ->successRedirectUrl(fn (Model $record): string => OrdemServicoResource::getUrl('custom', [
                //         'record' => $record,
                //     ])),
            ]);
    }
}
