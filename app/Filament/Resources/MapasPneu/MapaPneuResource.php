<?php

namespace App\Filament\Resources\MapasPneu;

use App\Filament\Resources\MapasPneu\Pages\ManageMapasPneu;
use App\Models\MapaPneu;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MapaPneuResource extends Resource
{
    protected static ?string $model = MapaPneu::class;

    protected static ?string $slug = 'mapas-pneu';

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static ?string $modelLabel = 'Mapa de Pneu';

    protected static ?string $pluralModelLabel = 'Mapas de Pneu';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dados do Mapa')
                    ->columns(12)
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Codigo')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(3),
                        TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(6),
                        TextInput::make('quantidade_posicoes')
                            ->label('Qtd. Posições')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->columnSpan(3),
                        Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(3),
                    ]),
                Section::make('Posições do Mapa')
                    ->description('Cadastre aqui as posições fixas atendidas por este mapa.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('posicoes')
                            ->label('Posições')
                            ->relationship()
                            ->orderColumn('sequencia')
                            ->defaultItems(0)
                            ->columns(12)
                            ->columnSpanFull()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn(array $state): ?string => $state['codigo'] ?? $state['nome'] ?? null)
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Codigo')
                                    ->required()
                                    ->maxLength(30)
                                    ->columnSpan(2),
                                TextInput::make('nome')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(120)
                                    ->columnSpan(5),
                                TextInput::make('sequencia')
                                    ->label('Sequencia')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('eixo_numero')
                                    ->label('Eixo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->columnSpan(2),
                                Toggle::make('ativo')
                                    ->label('Ativo')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(1),
                                Select::make('lado')
                                    ->label('Lado')
                                    ->options([
                                        'ESQUERDO' => 'Esquerdo',
                                        'DIREITO' => 'Direito',
                                        'CENTRO' => 'Centro',
                                    ])
                                    ->default('CENTRO')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(3),
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
                                    ->native(false)
                                    ->columnSpan(3),
                                Select::make('tipo_posicao')
                                    ->label('Tipo da Posição')
                                    ->options([
                                        'DIRECIONAL' => 'Direcional',
                                        'TRACAO' => 'Tração',
                                        'LIVRE' => 'Livre',
                                        'RESERVA' => 'Reserva',
                                    ])
                                    ->default('LIVRE')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(4),
                                Toggle::make('aceita_pneu_reserva')
                                    ->label('Aceita Pneu Reserva')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantidade_posicoes')
                    ->label('Qtd. Posições')
                    ->sortable(),
                TextColumn::make('posicoes_count')
                    ->label('Posições Cadastradas')
                    ->counts('posicoes'),
                TextColumn::make('veiculos_count')
                    ->label('Veículos')
                    ->counts('veiculos'),
                IconColumn::make('ativo')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => ManageMapasPneu::route('/'),
        ];
    }
}
