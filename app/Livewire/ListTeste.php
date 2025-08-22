<?php

namespace App\Livewire;

use App\Models;
use App\Enum;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
                    ->formatStateUsing(fn (Models\ItemOrdemServico $record): string => $record->servico->codigo . ' - ' . $record->servico->descricao)
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
                TextColumn::make('comentarios.conteudo')
                    ->label('Comentários')
                    ->html()
                    ->wrap()
                    ->size(TextSize::ExtraSmall)
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->width('2%')
                    ->visibleFrom('xl'),
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
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('id', 'desc')
            ->filters([

            ])
            ->recordActions([
                // ...
            ])
            ->toolbarActions([
                // ...
            ])
            ->recordClasses(fn (Models\ItemOrdemServico $record) => match ($record->status) {
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
