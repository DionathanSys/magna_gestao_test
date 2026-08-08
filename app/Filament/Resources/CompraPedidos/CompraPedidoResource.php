<?php

namespace App\Filament\Resources\CompraPedidos;

use App\Filament\Resources\CompraPedidos\Pages\CreateCompraPedido;
use App\Filament\Resources\CompraPedidos\Pages\EditCompraPedido;
use App\Filament\Resources\CompraPedidos\Pages\ListCompraPedidos;
use App\Filament\Resources\Parceiros\ParceiroResource;
use App\Models\CompraOrdem;
use App\Models\CompraPedido;
use App\Models\CompraPedidoItem;
use App\Models\EstoqueProduto;
use App\Models\Parceiro;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class CompraPedidoResource extends Resource
{
    protected static ?string $model = CompraPedido::class;

    protected static string|UnitEnum|null $navigationGroup = 'Estoque';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $modelLabel = 'Pedido de Compra';

    protected static ?string $pluralModelLabel = 'Pedidos de Compra';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(4)->components([
            TextInput::make('numero')
                ->label('Número')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Textarea::make('observacoes')
                ->label('Observações')
                ->columnSpan(3),
            Repeater::make('itens')
                ->label('Itens')
                ->relationship()
                ->schema([
                    Select::make('estoque_produto_id')
                        ->label('Produto')
                        ->relationship('produto', 'nome')
                        ->getOptionLabelFromRecordUsing(fn (EstoqueProduto $record): string => $record->codigo.' - '.$record->nome)
                        ->searchable(['codigo', 'nome'])
                        ->preload()
                        ->required(),
                    TextInput::make('quantidade_pedida')
                        ->label('Quantidade')
                        ->numeric()
                        ->minValue(0.0001)
                        ->required(),
                ])
                ->columns(2)
                ->minItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('itens')->withSum('itens', 'quantidade_pedida')->withSum('itens', 'quantidade_recebida'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
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
                TextColumn::make('itens_count')
                    ->label('Itens')
                    ->numeric(),
                TextColumn::make('itens_sum_quantidade_pedida')
                    ->label('Qtde. pedida')
                    ->numeric(4, ',', '.'),
                TextColumn::make('itens_sum_quantidade_recebida')
                    ->label('Qtde. recebida')
                    ->numeric(4, ',', '.'),
                TextColumn::make('created_at')
                    ->label('Criado em')
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
                self::gerarOrdemAction(),
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
            'index' => ListCompraPedidos::route('/'),
            'create' => CreateCompraPedido::route('/create'),
            'edit' => EditCompraPedido::route('/{record}/edit'),
        ];
    }

    private static function gerarOrdemAction(): Action
    {
        return Action::make('gerar_ordem_compra')
            ->label('Gerar ordem')
            ->icon('heroicon-o-document-plus')
            ->visible(fn (CompraPedido $record): bool => $record->status !== 'atendido')
            ->schema(fn (CompraPedido $record): array => [
                Select::make('parceiro_id')
                    ->label('Fornecedor')
                    ->options(fn (): array => Parceiro::query()->orderBy('nome')->pluck('nome', 'id')->all())
                    ->createOptionForm(fn (Schema $schema) => ParceiroResource::form($schema))
                    ->createOptionUsing(fn (array $data): int => Parceiro::query()->create($data)->getKey())
                    ->searchable()
                    ->required(),
                DatePicker::make('previsao_entrega_em')
                    ->label('Previsão de entrega'),
                Textarea::make('observacoes')
                    ->label('Observações')
                    ->columnSpanFull(),
                Repeater::make('itens')
                    ->label('Itens da ordem')
                    ->schema([
                        Hidden::make('compra_pedido_item_id')
                            ->required(),
                        TextInput::make('codigo_produto')
                            ->label('Código')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state) use ($record): void {
                                $pedidoItem = self::buscarItemPedidoPorCodigo($record, $state);

                                $set('compra_pedido_item_id', $pedidoItem?->id);
                                $set('produto_nome', $pedidoItem?->produto?->nome);
                                $set('quantidade_prevista', $pedidoItem ? max(0, (float) $pedidoItem->quantidade_pedida - (float) $pedidoItem->quantidade_recebida) : null);
                            })
                            ->helperText('Informe o código de um produto existente neste pedido.'),
                        TextInput::make('produto_nome')
                            ->label('Produto')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('quantidade_prevista')
                            ->label('Quantidade prevista')
                            ->numeric()
                            ->minValue(0.0001)
                            ->required(),
                    ])
                    ->columns(3)
                    ->minItems(1)
                    ->columnSpanFull(),
            ])
            ->action(function (CompraPedido $record, array $data): void {
                $ordem = CompraOrdem::query()->create([
                    'compra_pedido_id' => $record->id,
                    'parceiro_id' => $data['parceiro_id'],
                    'previsao_entrega_em' => $data['previsao_entrega_em'] ?? null,
                    'observacoes' => $data['observacoes'] ?? null,
                    'status' => 'aberto',
                ]);

                foreach ($data['itens'] as $itemData) {
                    $pedidoItem = $record->itens()->findOrFail($itemData['compra_pedido_item_id']);
                    $quantidadePrevista = (float) $itemData['quantidade_prevista'];
                    $quantidadePendente = max(0, (float) $pedidoItem->quantidade_pedida - (float) $pedidoItem->quantidade_recebida);

                    if ($quantidadePrevista > $quantidadePendente) {
                        throw ValidationException::withMessages([
                            'itens' => 'A quantidade prevista não pode ultrapassar a quantidade pendente do pedido.',
                        ]);
                    }

                    $ordem->itens()->create([
                        'compra_pedido_item_id' => $pedidoItem->id,
                        'estoque_produto_id' => $pedidoItem->estoque_produto_id,
                        'quantidade_prevista' => $quantidadePrevista,
                    ]);
                }
            });
    }

    private static function buscarItemPedidoPorCodigo(CompraPedido $pedido, ?string $codigo): ?CompraPedidoItem
    {
        if (blank($codigo)) {
            return null;
        }

        return $pedido->itens()
            ->whereHas('produto', fn (Builder $query): Builder => $query->where('codigo', trim($codigo)))
            ->with('produto:id,codigo,nome')
            ->first();
    }
}
