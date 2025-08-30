<?php

namespace App\Livewire;

use App\Models;
use App\Enum;
use App\Filament\Resources\Servicos\Schemas\ServicoForm;
use App\Services;
use Filament\Actions\{Action, ActionGroup, DeleteAction, EditAction};
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Livewire\Component;

class ListTeste extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public Models\OrdemServico $ordemServico;

    public function mount(Models\OrdemServico $ordemServico): void
    {
        $this->ordemServico = $ordemServico;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Serviços')
            ->query(Models\ItemOrdemServico::query()
                ->where('ordem_servico_id', $this->ordemServico->id)
                ->with(['servico', 'planoPreventivo']))
            ->columns([
                TextColumn::make('servico.descricao')
                    ->label('Serviço')
                    ->weight(FontWeight::Medium)
                    ->formatStateUsing(fn(Models\ItemOrdemServico $record): string => $record->servico->codigo . ' - ' . $record->servico->descricao)
                    ->description(function (Models\ItemOrdemServico $record) {
                        if ($record->observacao) {
                            return $record->observacao . ($record->posicao ? ' - Pos: ' . $record->posicao : '');
                        }
                        return $record->posicao ? 'Pos: ' . $record->posicao : '';
                    })
                    ->width('1%'),
                TextColumn::make('planoPreventivo.descricao')
                    ->label('Plano Preventivo')
                    ->width('1%')
                    ->placeholder('N/A')
                    ->visibleFrom('2xl')
                    ->limit(10, end: ' ...')
                    ->tooltip(fn(Models\ItemOrdemServico $record): string => $record->planoPreventivo->descricao)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->width('1%')
                    ->badge('success'),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Criado Em')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Editado Em')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comentarios.conteudo')
                    ->label('Comentários')
                    ->html()
                    ->wrap()
                    ->size(TextSize::ExtraSmall)
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->visibleFrom('xl'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    Action::make('visualizar-comentarios')
                        ->modalHeading('Comentários')
                        ->slideOver()
                        ->modalSubmitAction(false)
                        ->schema([
                            \Filament\Infolists\Components\RepeatableEntry::make('comentarios')
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('conteudo')
                                        ->label('Comentário')
                                        ->html(),
                                    \Filament\Infolists\Components\TextEntry::make('created_at')
                                        ->label('Criado em')
                                        ->dateTime('d/m/Y H:i'),
                                ])
                        ])->icon('heroicon-o-chat-bubble-left-ellipsis'),
                    Action::make('comentarios')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->schema([
                            RichEditor::make('conteudo')
                                ->label('Comentário')
                                ->required()
                                ->maxLength(500),
                        ])
                        ->action(function (array $data, Models\ItemOrdemServico $item) {
                            $item->comentarios()->create([
                                'veiculo_id'    => $item->ordemServico->veiculo_id,
                                'conteudo'      => $data['conteudo'],
                            ]);
                        }),
                    EditAction::make(),
                    DeleteAction::make()
                        ->action(function (Models\ItemOrdemServico $itemOrdemServico) {
                            Services\OrdemServico\ItemOrdemServicoService::delete($itemOrdemServico);
                        })
                        ->requiresConfirmation(),
                ])->icon('heroicon-o-bars-3-center-left')
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                CreateAction::make()
                    ->label('Serviço')
                    ->icon('heroicon-o-plus')
                    ->schema(
                        fn(Schema $schema) => $schema
                            ->columns([
                                'sm' => 1,
                                'md' => 4,
                                'lg' => 8,
                            ])
                            ->components(
                                [
                                    self::getServicoIdFormField()
                                        ->columnStart(1)
                                        ->columnSpan([
                                            'sm' => 1,
                                            'md' => 2,
                                            'lg' => 3
                                        ]),
                                    self::getControlaPosicaoFormField()
                                        ->columnSpan([
                                            'sm' => 1,
                                            'md' => 1,
                                            'lg' => 2
                                        ]),
                                    self::getPosicaoFormField()
                                        ->columnSpan([
                                            'sm' => 1,
                                            'md' => 1,
                                            'lg' => 2
                                        ]),
                                    self::getStatusFormField()
                                        ->columnSpan([
                                            'sm' => 1,
                                            'md' => 2,
                                            'lg' => 3
                                        ]),
                                    self::getObersavacaoFormField()
                                        ->columnSpanFull(),
                                ]
                            )
                    )
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by'] = Auth::user()->id;
                        return $data;
                    }),
            ])
            ->recordClasses(fn(Models\ItemOrdemServico $record) => match ($record->status) {
                Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE => 'bg-yellow-100',
                Enum\OrdemServico\StatusOrdemServicoEnum::CANCELADO => 'bg-red-100',
                Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO => 'bg-green-100',
                default => null,
            });
    }

    public function render()
    {
        return view('livewire.list-teste');
    }

    public static function getServicoIdFormField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('servico_id')
            ->label('Serviço')
            ->required()
            ->relationship('servico', 'descricao')
            ->createOptionForm(fn(Schema $schema) => ServicoForm::configure($schema))
            ->editOptionForm(fn(Schema $schema) => ServicoForm::configure($schema))
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(function (Set $set, $state) {
                if ($state) {
                    $servico = \App\Models\Servico::find($state);
                    $set('controla_posicao', $servico?->controla_posicao ? true : false);
                } else {
                    $set('controla_posicao', false);
                }
            });
    }

    public static function getControlaPosicaoFormField(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('controla_posicao')
            ->label('Controla Posição')
            ->inline(false)
            ->disabled()
            ->live();
    }

    public static function getPosicaoFormField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('posicao')
            ->label('Posição')
            ->requiredIf('controla_posicao', true)
            ->minLength(2)
            ->maxLength(5);
    }

    public static function getObersavacaoFormField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('observacao')
            ->label('Observação')
            ->maxLength(200);
    }

    public static function getStatusFormField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('status')
            ->label('Status')
            ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
            ->default(Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE->value)
            ->required();
    }
}
