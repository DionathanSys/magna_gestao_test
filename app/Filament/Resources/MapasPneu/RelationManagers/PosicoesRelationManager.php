<?php

namespace App\Filament\Resources\MapasPneu\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PosicoesRelationManager extends RelationManager
{
    protected static string $relationship = 'posicoes';

    protected static ?string $title = 'Posicoes do Mapa';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label('Codigo')
                    ->required()
                    ->maxLength(30)
                    ->uppercase(),
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(120),
                TextInput::make('sequencia')
                    ->label('Sequencia')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('eixo_numero')
                    ->label('Eixo')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Select::make('lado')
                    ->label('Lado')
                    ->options([
                        'ESQUERDO' => 'Esquerdo',
                        'DIREITO' => 'Direito',
                        'CENTRO' => 'Centro',
                    ])
                    ->default('CENTRO')
                    ->required()
                    ->native(false),
                Select::make('conjunto')
                    ->label('Conjunto')
                    ->options([
                        'SIMPLES' => 'Simples',
                        'INTERNO' => 'Interno',
                        'EXTERNO' => 'Externo',
                        'RESERVA' => 'Reserva',
                    ])
                    ->default('SIMPLES')
                    ->required()
                    ->native(false),
                Select::make('tipo_posicao')
                    ->label('Tipo da Posicao')
                    ->options([
                        'DIRECIONAL' => 'Direcional',
                        'TRACAO' => 'Tracao',
                        'LIVRE' => 'Livre',
                        'RESERVA' => 'Reserva',
                    ])
                    ->default('LIVRE')
                    ->required()
                    ->native(false),
                Toggle::make('aceita_pneu_reserva')
                    ->label('Aceita Pneu Reserva')
                    ->default(false),
                Toggle::make('ativo')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->defaultSort('sequencia')
            ->columns([
                TextColumn::make('sequencia')
                    ->label('Seq.')
                    ->sortable(),
                TextColumn::make('codigo')
                    ->label('Codigo')
                    ->searchable(),
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('eixo_numero')
                    ->label('Eixo')
                    ->sortable(),
                TextColumn::make('lado')
                    ->label('Lado'),
                TextColumn::make('conjunto')
                    ->label('Conjunto'),
                TextColumn::make('tipo_posicao')
                    ->label('Tipo'),
                IconColumn::make('aceita_pneu_reserva')
                    ->label('Reserva')
                    ->boolean(),
                IconColumn::make('ativo')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nova Posicao'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
