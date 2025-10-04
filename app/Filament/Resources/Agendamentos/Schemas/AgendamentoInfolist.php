<?php

namespace App\Filament\Resources\Agendamentos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AgendamentoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('veiculo.id'),
                TextEntry::make('ordemServico.id'),
                TextEntry::make('data_agendamento')
                    ->date(),
                TextEntry::make('data_limite')
                    ->date(),
                TextEntry::make('data_realizado')
                    ->date(),
                TextEntry::make('servico.id'),
                TextEntry::make('planoPreventivo.id'),
                TextEntry::make('posicao'),
                TextEntry::make('status'),
                TextEntry::make('observacao'),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('updated_by')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('parceiro.id'),
            ]);
    }
}
