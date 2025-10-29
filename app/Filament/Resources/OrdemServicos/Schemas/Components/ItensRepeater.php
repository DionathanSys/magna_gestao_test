<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\{Models, Enum, Services};
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ItensRepeater
{
    public static function make(): Repeater
    {
        return Repeater::make('itens')
            ->label('Itens')
            ->relationship()
            // ->orderColumn('data_agendamento')
            // ->columns(12)
            // ->collapsible()
            ->table([
                TableColumn::make('servico.descricao'),
                TableColumn::make('observacao'),
                TableColumn::make('posicao'),
                TableColumn::make('status'),
            ])
            ->schema([
                TextEntry::make('servico.descricao'),
                TextEntry::make('observacao'),
                TextEntry::make('posicao'),
                TextEntry::make('status'),
            ])->compact();
    }
}
