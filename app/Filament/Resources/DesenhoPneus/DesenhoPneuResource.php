<?php

namespace App\Filament\Resources\DesenhoPneus;

use App\Filament\Resources\DesenhoPneus\Pages\ManageDesenhoPneus;
use App\Models\DesenhoPneu;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DesenhoPneuResource extends Resource
{
    protected static ?string $model = DesenhoPneu::class;

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static ?string $recordTitleAttribute = 'descricao';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->columnSpan(12)
                    ->afterLabel('Descrição que define o desenho do pneu')
                    ->belowContent('Se for novo, usar o mesmo valor do modelo')
                    ->nullable()
                    ->maxLength(50),
                TextInput::make('modelo')
                    ->label('Modelo')
                    ->columnSpan(12)
                    ->afterLabel('Novo: Modelo da Carcaça - Recapado: Modelo da Banda')
                    ->nullable()
                    ->maxLength(100),
                Select::make('estado_pneu')
                    ->label('Estado do Pneu')
                    ->columnSpan(4)
                    ->native(false)
                    ->options([
                        'NOVO'     => 'NOVO',
                        'RECAPADO' => 'RECAPADO',
                    ])
                    ->default('RECAPADO'),
                TextInput::make('medida')
                    ->label('Medida Borracha')
                    ->columnSpan(2)
                    ->nullable()
                    ->maxLength(50),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descricao')
            ->columns([
                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->width('1%')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('modelo')
                    ->label('Modelo')
                    ->width('1%')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('medida')
                    ->label('Medida Borracha (mm)')
                    ->width('1%')
                    ->wrapHeader()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('estado_pneu')
                    ->label('Estado pneu')
                    ->width('1%')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
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
            'index' => ManageDesenhoPneus::route('/'),
        ];
    }
}
