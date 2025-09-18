<?php

namespace App\Livewire;

use App\Models;
use App\Enum;
use App\Filament\Resources\OrdemServicos\Actions;
use App\Filament\Resources\Servicos\Schemas\ServicoForm;
use App\Services;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
            // ->query(Models\ItemOrdemServico::query()
            //     ->where('ordem_servico_id', $this->ordemServico->id)
            //     ->with(['servico', 'planoPreventivo']))
            ->relationship(fn() => $this->ordemServico->itens())
            ->inverseRelationship('ordemServico')
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
                    Actions\EditarServicoOrdemServicoAction::make(fn(Models\ItemOrdemServico $record) => $record->id),
                    DeleteAction::make()
                        ->action(function (Models\ItemOrdemServico $itemOrdemServico) {
                            Services\OrdemServico\ItemOrdemServicoService::delete($itemOrdemServico);
                        })
                        ->successNotificationTitle(null)
                        ->requiresConfirmation(),
                ])->icon('heroicon-o-bars-3-center-left')
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                Actions\VincularServicoOrdemServicoAction::make($this->ordemServico->id)
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


}
