<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Pneus\PneuResource;
use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Models;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class KmRodadoPneu extends BaseWidget
{
    protected string $view = 'filament.widgets.km-rodado-pneu';

    protected int $queryCount;

    protected int $perPage = 15;

    public function boot()
    {
        $this->queryCount = $this->getBaseQuery()->count();
    }

    protected function getBaseQuery(): Builder
    {
        $threshold = 7000;
        $pneuTable = (new Models\PneuPosicaoVeiculo())->getTable();

        return Models\PneuPosicaoVeiculo::query()
            ->with(['pneu', 'veiculo', 'veiculo.kmAtual'])
            // garante que exista kmAtual relacionado e faz a comparação: kmAtual.quilometragem - pneu_posicao_veiculos.km_inicial > 7000
            ->whereHas('veiculo.kmAtual', function (Builder $q) use ($pneuTable, $threshold) {
                $kmTable = $q->getQuery()->from;

                // evita registros com km_inicial null e quilometragem null
                $q->whereNotNull("{$kmTable}.quilometragem")
                  ->whereRaw("{$kmTable}.quilometragem - {$pneuTable}.km_inicial > ?", [$threshold]);
            })
            ->aplicados();
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseTable()
            ->heading(null);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption($this->perPage)
            ->paginated(fn() => $this->queryCount > $this->perPage)
            ->query($this->getBaseQuery())
            ->columns([
                TextColumn::make('pneu.numero_fogo')
                    ->label('Nº Fogo')
                    ->width('1%')
                    ->numeric('0', '', '')
                    ->sortable()
                    ->url(fn(Models\PneuPosicaoVeiculo $record) => PneuResource::getUrl('view', ['record' => $record->pneu_id ?? 0]))
                    ->openUrlInNewTab(),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->url(fn($record): string => VeiculoResource::getUrl('edit', ['record' => $record->veiculo->id]))
                    ->openUrlInNewTab(),
                TextColumn::make('km_inicial')
                    ->label('Km Rodado')
                    ->width('1%')
                    ->sortable()
                    ->state(fn (Models\PneuPosicaoVeiculo $record): string => $record->km_inicial ? (($record->veiculo->kmAtual->quilometragem ?? 0) - $record->km_inicial) : 'N/A')
                    ->numeric(0, ',', '.'),
                TextColumn::make('eixo')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('posicao')
                    ->label('Posição')
                    ->width('1%')
                    ->searchable(isIndividual: true),
            ])
            ->groups([
                Group::make('Eixo')
                    ->collapsible(),
                Group::make('Posição')
                    ->collapsible(),
                Group::make('veiculo.placa')
                    ->label('Placa')
                    ->collapsible(),

            ])
            ->defaultGroup('veiculo.placa')
            ->paginated([10, 25, 50,])
            ->defaultPaginationPageOption(12)
            ->filters([
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    private function getRecordUrl(Models\PneuPosicaoVeiculo $record)
    {
        return null;
        // return MenuItemResource::getUrl('edit', [
        //     'record' => $record,
        // ]);
    }

    protected function getViewData(): array
    {
        return [
            'queryCount' => $this->queryCount,
            'message'    => $this->getMessage(),
            'table'      => $this->getTable(),
        ];
    }

    protected function getMessage(): HtmlString
    {
        if ($this->queryCount === 0) {
            return new HtmlString(<<<HTML
            <div class="text-gray-500">
                Nenhum registro encontrado.
            </div>
            HTML
            );
        }

        return new HtmlString(<<<HTML
        <div class="text-base tracking-[0.07rem] uppercase font-normal text-red-700 dark:text-red-400">
            Controle de Km Rodado dos Pneus - Total de Registros: {$this->queryCount}
        </div>
        HTML
        );
    }
}
