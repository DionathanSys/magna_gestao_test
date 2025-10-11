<?php

namespace App\Filament\Resources\OrdemServicos\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use App\Enum;
use App\Models;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;

use Filament\Schemas\Components\Utilities\Set;

class OrdemServicoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['md' => 2, 'xl' => 4])
            ->components([
                TextEntry::make('veiculo.placa')
                    ->label('Veículo')
                    ->badge()
                    ->color('primary')
                    ->columnSpan([
                        'md' => 2,
                        'xl' => 1,
                    ]),
                TextEntry::make('quilometragem')
                    ->label('Quilometragem')
                    ->columnSpan([
                        'md' => 2,
                        'xl' => 1,
                    ])
                    ->numeric(0, ',', '.')
                    ->placeholder('Não informado'),
                TextEntry::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->columnSpan([
                        'md' => 2,
                        'xl' => 1,
                    ]),
                TextEntry::make('data_inicio')
                    ->label('Data Início')
                    ->columnSpan([
                        'md' => 2,
                        'xl' => 1,
                    ])
                    ->dateTime('d/m/Y H:i'),
                RepeatableEntry::make('itens')
                    ->label('Serviços')
                    ->columnSpanFull()
                    ->columns(12)
                    ->table([
                        TableColumn::make('Código')->hiddenHeaderLabel(),
                        TableColumn::make('Serviço'),
                        TableColumn::make('Posição'),
                        TableColumn::make('Observação'),
                        TableColumn::make('Status'),
                    ])
                    ->schema([
                        TextEntry::make('servico.codigo')
                            ->columnSpan(1),
                        TextEntry::make('servico.descricao')
                            ->columnSpan(4)
                            ->formatStateUsing(fn(Models\ItemOrdemServico $item) => $item->servico->descricao),
                        TextEntry::make('posicao')
                            ->columnSpan(1)
                            ->placeholder(''),
                        TextEntry::make('observacao')
                            ->columnSpan(4)
                            ->prefix('Obs: ')
                            ->placeholder('Sem observações'),
                        TextEntry::make('status')
                            ->columnSpan(2)
                            ->badge()
                            ->color('primary'),
                    ]),
                RepeatableEntry::make('sankhyaId')
                    ->label('Ordens Sankhya')
                    ->columnSpanFull()
                    ->table([
                        TableColumn::make('Id'),
                        TableColumn::make('Número'),
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->columnSpan(4),
                        TextEntry::make('ordem_sankhya_id')
                            ->columnSpan(4),
                    ]),
            ]);
    }

}
