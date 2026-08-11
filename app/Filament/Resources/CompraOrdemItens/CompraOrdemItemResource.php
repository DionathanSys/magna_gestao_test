<?php

namespace App\Filament\Resources\CompraOrdemItens;

use App\Filament\Resources\CompraOrdemItens\Pages\ListCompraOrdemItens;
use App\Filament\Resources\CompraOrdens\CompraOrdemResource;
use App\Models\CompraOrdemItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CompraOrdemItemResource extends Resource
{
    protected static ?string $model = CompraOrdemItem::class;

    protected static string|UnitEnum|null $navigationGroup = 'Estoque';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Item de Ordem de Compra';

    protected static ?string $pluralModelLabel = 'Itens de Ordens de Compra';

    protected static ?string $slug = 'compra-ordem-itens';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'ordem:id,compra_pedido_id,parceiro_id,status,previsao_entrega_em',
                'ordem.parceiro:id,nome',
                'ordem.pedido:id,numero',
                'produto:id,codigo,nome',
            ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ordem.id')
                    ->label('Ordem')
                    ->sortable()
                    ->url(fn (CompraOrdemItem $record): string => CompraOrdemResource::getUrl('edit', ['record' => $record->ordem])),
                TextColumn::make('ordem.pedido.numero')
                    ->label('Pedido')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ordem.parceiro.nome')
                    ->label('Fornecedor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('produto.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('produto.nome')
                    ->label('Produto')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('ordem.status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'atendido' => 'success',
                        'parcial' => 'warning',
                        'cancelado' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('quantidade_prevista')
                    ->label('Qtde. prevista')
                    ->numeric(4, ',', '.')
                    ->sortable(),
                TextColumn::make('quantidade_recebida')
                    ->label('Qtde. recebida')
                    ->numeric(4, ',', '.')
                    ->sortable(),
                TextColumn::make('quantidade_pendente')
                    ->label('Qtde. pendente')
                    ->state(fn (CompraOrdemItem $record): float => max(0, (float) $record->quantidade_prevista - (float) $record->quantidade_recebida))
                    ->numeric(4, ',', '.'),
                TextColumn::make('ordem.previsao_entrega_em')
                    ->label('Previsão')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status_ordem')
                    ->label('Status da ordem')
                    ->options([
                        'aberto' => 'Aberto',
                        'parcial' => 'Parcial',
                        'atendido' => 'Atendido',
                        'cancelado' => 'Cancelado',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $status): Builder => $query->whereHas('ordem', fn (Builder $query): Builder => $query->where('status', $status))
                    )),
                SelectFilter::make('fornecedor')
                    ->label('Fornecedor')
                    ->relationship('ordem.parceiro', 'nome')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('pedido')
                    ->label('Pedido')
                    ->relationship('ordem.pedido', 'numero')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('estoque_produto_id')
                    ->label('Produto')
                    ->relationship('produto', 'nome')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->codigo.' - '.$record->nome)
                    ->searchable(['codigo', 'nome'])
                    ->preload()
                    ->multiple(),
                TernaryFilter::make('pendente')
                    ->label('Com pendência')
                    ->placeholder('Todos')
                    ->trueLabel('Sim')
                    ->falseLabel('Não')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereColumn('quantidade_recebida', '<', 'quantidade_prevista'),
                        false: fn (Builder $query): Builder => $query->whereColumn('quantidade_recebida', '>=', 'quantidade_prevista'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->groups([
                Group::make('ordem.parceiro.nome')
                    ->label('Fornecedor')
                    ->collapsible(),
                Group::make('ordem.pedido.numero')
                    ->label('Pedido')
                    ->collapsible(),
            ])
            ->defaultGroup('ordem.parceiro.nome')
            ->recordActions([
                Action::make('abrir_ordem')
                    ->label('Abrir ordem')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CompraOrdemItem $record): string => CompraOrdemResource::getUrl('edit', ['record' => $record->ordem])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompraOrdemItens::route('/'),
        ];
    }
}
