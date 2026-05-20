<?php

namespace App\Filament\Resources\PneuMedidas;

use App\Filament\Resources\PneuMedidas\Pages\ManagePneuMedidas;
use App\Models\PneuMedida;
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

class PneuMedidaResource extends Resource
{
    protected static ?string $model = PneuMedida::class;

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static ?string $modelLabel = 'Medida de Pneu';

    protected static ?string $pluralModelLabel = 'Medidas de Pneu';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(255),
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->maxLength(255),
                Toggle::make('ativo')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('descricao')
                    ->searchable(),
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
            'index' => ManagePneuMedidas::route('/'),
        ];
    }
}
