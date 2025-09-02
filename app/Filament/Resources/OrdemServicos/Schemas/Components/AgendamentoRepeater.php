<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\{Models, Enum, Services};
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class AgendamentoRepeater
{
    public static function make(): Repeater
    {
        return Repeater::make('agendamentosPendentes')
            ->label('Agendamentos')
            ->disabled()
            ->relationship()
            ->orderColumn('data_agendamento')
            ->columns(12)
            ->deletable(fn(): bool => Auth::user()->is_admin)
            ->addable(false)
            ->collapsible()
            ->schema([
                Hidden::make('id')
                    ->label('ID'),
                TextEntry::make('servico.descricao')
                    ->label('Descrição do Serviço')
                    ->columnSpan(12),
                TextEntry::make('data_agendamento')
                    ->label('Data do Agendamento')
                    ->columnSpan(6)
                    ->placeholder('Sem data definida')
                    ->date('d/m/Y'),
                TextEntry::make('data_limite')
                    ->label('Data Limite Realização')
                    ->columnSpan(6)
                    ->placeholder('Sem data definida')
                    ->date('d/m/Y'),
                TextEntry::make('observacao')
                    ->label('Observação')
                    ->placeholder('Sem observação')
                    ->columnSpan(6),
                TextEntry::make('parceiro.nome')
                    ->label('Fornecedor')
                    ->placeholder('Sem fornecedor externo definido')
                    ->columnSpan(6),
            ])
            ->extraItemActions([
                Action::make('vincularAgendamento')
                    ->icon(Heroicon::Link)
                    ->action(function (array $arguments, Repeater $component, $state,$record): void {
                        $agendamento = Models\Agendamento::find($state[$arguments['item']]);
                        $service = new Services\Agendamento\AgendamentoService();
                        $service->vincularEmOrdemServico($agendamento);
                        if($service->hasError()) {
                            notify::error($service->getMessage());
                            return;
                        }
                        notify::success('Agendamento vinculado com sucesso!');
                    }),
            ]);
    }
}
