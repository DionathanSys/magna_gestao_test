<?php

namespace App\Filament\Resources\EstoqueProdutos;

use App\Filament\Resources\EstoqueProdutos\Pages\ManageEstoqueProdutos;
use App\Models\EstoqueProduto;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use UnitEnum;

class EstoqueProdutoResource extends Resource
{
    protected static ?string $model = EstoqueProduto::class;

    protected static string|UnitEnum|null $navigationGroup = 'Estoque';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $modelLabel = 'Produto de Estoque';

    protected static ?string $pluralModelLabel = 'Produtos de Estoque';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(4)->components([
            TextInput::make('codigo')
                ->label('Código')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(255)
                ->columnSpan(3),
            TextInput::make('saldo')
                ->label('Saldo')
                ->numeric()
                ->readOnly(),
            TextInput::make('estoque_minimo')
                ->label('Estoque mínimo')
                ->numeric()
                ->nullable(),
            TextInput::make('estoque_maximo')
                ->label('Estoque máximo')
                ->numeric()
                ->nullable(),
            Toggle::make('ativo')
                ->label('Ativo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->numeric(4, ',', '.')
                    ->sortable(),
                TextColumn::make('status_estoque')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'abaixo_minimo' => 'Abaixo mínimo',
                        'acima_maximo' => 'Acima máximo',
                        default => 'Normal',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'abaixo_minimo' => 'danger',
                        'acima_maximo' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('estoque_minimo')
                    ->label('Mín.')
                    ->numeric(4, ',', '.')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('estoque_maximo')
                    ->label('Máx.')
                    ->numeric(4, ',', '.')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('valor_reposicao_centavos')
                    ->label('Valor reposição')
                    ->money('BRL', 100)
                    ->sortable(),
                TextColumn::make('custo_total_centavos')
                    ->label('Custo total')
                    ->money('BRL', 100)
                    ->sortable(),
                TextColumn::make('ultimo_movimento_em')
                    ->label('Últ. mov.')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('dias_obsolescencia')
                    ->label('Dias parado')
                    ->numeric(0, ',', '.')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('previsao_consumo_dias')
                    ->label('Prev. consumo')
                    ->suffix(' dias')
                    ->placeholder('-')
                    ->sortable(),
                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status_estoque')
                    ->label('Status')
                    ->options([
                        'abaixo_minimo' => 'Abaixo mínimo',
                        'normal' => 'Normal',
                        'acima_maximo' => 'Acima máximo',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'abaixo_minimo' => $query->whereNotNull('estoque_minimo')->whereColumn('saldo', '<', 'estoque_minimo'),
                        'acima_maximo' => $query->whereNotNull('estoque_maximo')->whereColumn('saldo', '>', 'estoque_maximo'),
                        'normal' => $query
                            ->where(fn ($query) => $query->whereNull('estoque_minimo')->orWhereColumn('saldo', '>=', 'estoque_minimo'))
                            ->where(fn ($query) => $query->whereNull('estoque_maximo')->orWhereColumn('saldo', '<=', 'estoque_maximo')),
                        default => $query,
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ManageEstoqueProdutos::route('/'),
        ];
    }
}
