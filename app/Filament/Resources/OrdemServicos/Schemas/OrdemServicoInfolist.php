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
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
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
                    ->schema([
                        TextEntry::make('servico.codigo')
                            ->label('Código')
                            ->columnSpan(1),
                        TextEntry::make('servico.descricao')
                            ->label('Serviço')
                            ->columnSpan(4)
                            ->formatStateUsing(fn(Models\ItemOrdemServico $item) => $item->servico->descricao),
                        TextEntry::make('posicao')
                            ->label('Posição')
                            ->columnSpan(1)
                            ->placeholder('N/A'),
                        TextEntry::make('observacao')
                            ->label('Observação')
                            ->columnSpan(4)
                            ->placeholder('Sem observações'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->columnSpan(2)
                            ->badge()
                            ->color('primary'),
                    ]),
            ]);
    }

}
