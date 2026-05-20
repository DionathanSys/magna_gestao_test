<?php

namespace App\Filament\Resources\PneuModelos;

use App\Filament\Resources\PneuModelos\Pages\ManagePneuModelos;
use App\Models\PneuModelo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PneuModeloResource extends Resource
{
    protected static ?string $model = PneuModelo::class;

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static ?string $modelLabel = 'Modelo de Pneu';

    protected static ?string $pluralModelLabel = 'Modelos de Pneu';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Toggle::make('ativo')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->columns([
                TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('ativo')
                    ->boolean(),
                TextColumn::make('pneus_count')
                    ->label('Pneus')
                    ->counts('pneus'),
                TextColumn::make('created_at')
                    ->label('Criado em')
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
            'index' => ManagePneuModelos::route('/'),
        ];
    }
}
