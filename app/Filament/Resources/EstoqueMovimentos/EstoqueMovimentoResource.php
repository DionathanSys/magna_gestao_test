<?php

namespace App\Filament\Resources\EstoqueMovimentos;

use App\Filament\Resources\EstoqueMovimentos\Pages\ListEstoqueMovimentos;
use App\Models\EstoqueMovimento;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EstoqueMovimentoResource extends Resource
{
    protected static ?string $model = EstoqueMovimento::class;

    protected static string|UnitEnum|null $navigationGroup = 'Estoque';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $modelLabel = 'Movimento de Estoque';

    protected static ?string $pluralModelLabel = 'Movimentos de Estoque';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('produto:id,codigo,nome'))
            ->defaultSort('data_movimento', 'desc')
            ->columns([
                TextColumn::make('data_movimento')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('produto.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('produto.nome')
                    ->label('Produto')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('quantidade_entrada')
                    ->label('Entrada')
                    ->numeric(4, ',', '.')
                    ->sortable(),
                TextColumn::make('quantidade_saida')
                    ->label('Saída')
                    ->numeric(4, ',', '.')
                    ->sortable(),
                TextColumn::make('saldo_apos_movimento')
                    ->label('Saldo')
                    ->numeric(4, ',', '.')
                    ->placeholder('-'),
                TextColumn::make('origem')
                    ->label('Origem')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('data_movimento')
                    ->form([
                        DatePicker::make('data_inicio')->label('Data inicial'),
                        DatePicker::make('data_fim')->label('Data final'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['data_inicio'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_movimento', '>=', $date))
                        ->when($data['data_fim'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_movimento', '<=', $date))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstoqueMovimentos::route('/'),
        ];
    }
}
