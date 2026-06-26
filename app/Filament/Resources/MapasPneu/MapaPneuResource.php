<?php

namespace App\Filament\Resources\MapasPneu;

use App\Filament\Resources\MapasPneu\Pages\ManageMapasPneu;
use App\Filament\Resources\MapasPneu\RelationManagers\PosicoesRelationManager;
use App\Models\MapaPneu;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
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
            ->components([
                TextInput::make('codigo')
                    ->label('Codigo')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(100),
                TextInput::make('quantidade_posicoes')
                    ->label('Qtd. Posições')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('Campo informativo nesta fase. Depois podera ser sincronizado pelas posições cadastradas.'),
                Toggle::make('ativo')
                    ->label('Ativo')
                    ->default(true)
                    ->inline(false),
                Textarea::make('descricao')
                    ->label('Descricao')
                    ->rows(3)
                    ->columnSpanFull(),
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

    public static function getRelations(): array
    {
        return [
            PosicoesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMapasPneu::route('/'),
        ];
    }
}
