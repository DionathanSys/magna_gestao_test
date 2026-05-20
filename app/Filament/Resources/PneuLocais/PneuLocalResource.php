<?php

namespace App\Filament\Resources\PneuLocais;

use App\Filament\Resources\PneuLocais\Pages\ManagePneuLocais;
use App\Models\PneuLocal;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PneuLocalResource extends Resource
{
    protected static ?string $model = PneuLocal::class;

    protected static ?string $slug = 'pneu-locais';

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static ?string $modelLabel = 'Local de Pneu';

    protected static ?string $pluralModelLabel = 'Locais de Pneu';

    protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tipo')
                    ->label('Tipo')
                    ->maxLength(255),
                Select::make('parceiro_id')
                    ->label('Parceiro')
                    ->relationship('parceiro', 'nome')
                    ->searchable()
                    ->preload(),
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
                TextColumn::make('tipo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parceiro.nome')
                    ->label('Parceiro')
                    ->placeholder('N/A'),
                IconColumn::make('ativo')
                    ->boolean(),
                TextColumn::make('pneus_count')
                    ->label('Pneus')
                    ->counts('pneus'),
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
            'index' => ManagePneuLocais::route('/'),
        ];
    }
}
