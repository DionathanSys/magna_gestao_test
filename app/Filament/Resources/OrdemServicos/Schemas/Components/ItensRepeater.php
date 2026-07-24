<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\TextEntry;

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
