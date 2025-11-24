<?php

namespace App\Filament\Resources\CustoDiversos;

use App\Filament\Resources\CustoDiversos\Pages\ManageCustoDiversos;
use App\Models\CustoDiverso;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustoDiversoResource extends Resource
{
    protected static ?string $model = CustoDiverso::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->required(),
                DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->required(),
                KeyValue::make('descricao')
                    ->label('Descrição')
                    ->columnStart(1)
                    ->columnSpanFull()
                    ->required(),
                TextInput::make('custo_total')
                    ->label('Custo Total')
                    ->columnStart(1)
                    ->prefix('R$')
                    ->required()
                    ->numeric(),
                TextInput::make('quantidade_veiculos')
                    ->label('Quantidade Veículos')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->label('Data Início')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->label('Data Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('custo_total')
                    ->label('Custo Total')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('quantidade_veiculos')
                    ->label('Quantidade Veículos')
                    ->sortable(),
                TextColumn::make('custo_medio_por_veiculo')
                    ->label('Custo Médio por Veículo')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado Em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageCustoDiversos::route('/'),
        ];
    }
}
