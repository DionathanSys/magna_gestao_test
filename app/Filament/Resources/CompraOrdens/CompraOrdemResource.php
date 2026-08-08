<?php

namespace App\Filament\Resources\CompraOrdens;

use App\Filament\Resources\CompraOrdens\Pages\EditCompraOrdem;
use App\Filament\Resources\CompraOrdens\Pages\ListCompraOrdens;
use App\Models\CompraOrdem;
use App\Models\CompraOrdemItem;
use App\Models\CompraRecebimento;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class CompraOrdemResource extends Resource
{
    protected static ?string $model = CompraOrdem::class;

    protected static string|UnitEnum|null $navigationGroup = 'Estoque';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $modelLabel = 'Ordem de Compra';

    protected static ?string $pluralModelLabel = 'Ordens de Compra';

    protected static ?string $slug = 'compra-ordens';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(4)->components([
            Select::make('compra_pedido_id')
                ->label('Pedido')
                ->relationship('pedido', 'numero')
                ->disabled(),
            Select::make('parceiro_id')
                ->label('Fornecedor')
                ->relationship('parceiro', 'nome')
                ->searchable()
                ->required(),
            TextInput::make('status')
                ->label('Status')
                ->disabled(),
            Textarea::make('observacoes')
                ->label('Observações')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['pedido:id,numero', 'parceiro:id,nome'])->withSum('itens', 'quantidade_prevista')->withSum('itens', 'quantidade_recebida'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Ordem')
                    ->sortable(),
                TextColumn::make('pedido.numero')
                    ->label('Pedido')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parceiro.nome')
                    ->label('Fornecedor')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'atendido' => 'success',
                        'parcial' => 'warning',
                        'cancelado' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('previsao_entrega_em')
                    ->label('Previsão')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('itens_sum_quantidade_prevista')
                    ->label('Qtde. prevista')
                    ->numeric(4, ',', '.'),
                TextColumn::make('itens_sum_quantidade_recebida')
                    ->label('Qtde. recebida')
                    ->numeric(4, ',', '.'),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'aberto' => 'Aberto',
                        'parcial' => 'Parcial',
                        'atendido' => 'Atendido',
                        'cancelado' => 'Cancelado',
                    ]),
            ])
            ->recordActions([
                self::registrarRecebimentoAction(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompraOrdens::route('/'),
            'edit' => EditCompraOrdem::route('/{record}/edit'),
        ];
    }

    private static function registrarRecebimentoAction(): Action
    {
        return Action::make('registrar_recebimento')
            ->label('Receber')
            ->icon('heroicon-o-check-circle')
            ->visible(fn (CompraOrdem $record): bool => $record->status !== 'atendido')
            ->schema(fn (CompraOrdem $record): array => [
                DateTimePicker::make('recebido_em')
                    ->label('Recebido em')
                    ->default(now())
                    ->required(),
                Textarea::make('observacoes')
                    ->label('Observações')
                    ->columnSpanFull(),
                Repeater::make('itens')
                    ->label('Itens recebidos')
                    ->schema([
                        Select::make('compra_ordem_item_id')
                            ->label('Item')
                            ->options(fn (): array => $record->itens()->with('produto:id,codigo,nome')->get()->mapWithKeys(
                                fn (CompraOrdemItem $item): array => [$item->id => $item->produto->codigo.' - '.$item->produto->nome.' | pendente: '.number_format(max(0, (float) $item->quantidade_prevista - (float) $item->quantidade_recebida), 4, ',', '.')]
                            )->all())
                            ->required(),
                        TextInput::make('quantidade_recebida')
                            ->label('Quantidade recebida')
                            ->numeric()
                            ->minValue(0.0001)
                            ->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->columnSpanFull(),
            ])
            ->action(function (CompraOrdem $record, array $data): void {
                $recebimento = CompraRecebimento::query()->create([
                    'compra_ordem_id' => $record->id,
                    'recebido_em' => $data['recebido_em'],
                    'observacoes' => $data['observacoes'] ?? null,
                ]);

                foreach ($data['itens'] as $itemData) {
                    $ordemItem = $record->itens()->with('pedidoItem')->findOrFail($itemData['compra_ordem_item_id']);
                    $quantidadeRecebida = (float) $itemData['quantidade_recebida'];
                    $quantidadePendente = max(0, (float) $ordemItem->quantidade_prevista - (float) $ordemItem->quantidade_recebida);

                    if ($quantidadeRecebida > $quantidadePendente) {
                        throw ValidationException::withMessages([
                            'itens' => 'A quantidade recebida não pode ultrapassar a quantidade pendente da ordem.',
                        ]);
                    }

                    $recebimento->itens()->create([
                        'compra_ordem_item_id' => $ordemItem->id,
                        'estoque_produto_id' => $ordemItem->estoque_produto_id,
                        'quantidade_recebida' => $quantidadeRecebida,
                    ]);

                    $ordemItem->increment('quantidade_recebida', $quantidadeRecebida);
                    $ordemItem->pedidoItem->increment('quantidade_recebida', $quantidadeRecebida);
                }

                $record->refresh()->atualizarAtendimento();
                $record->pedido->refresh()->atualizarAtendimento();
            });
    }
}
