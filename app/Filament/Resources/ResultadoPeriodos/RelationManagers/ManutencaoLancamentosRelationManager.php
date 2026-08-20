<?php

namespace App\Filament\Resources\ResultadoPeriodos\RelationManagers;

use App\Filament\Resources\ManutencaoLancamentos\Tables\ManutencaoLancamentosTable;
use App\Models\ManutencaoLancamento;
use Carbon\Carbon;
use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManutencaoLancamentosRelationManager extends RelationManager
{
    protected static string $relationship = 'manutencaoLancamentos';

    protected static ?string $title = 'Custos de Manutenção';

    public function table(Table $table): Table
    {
        return ManutencaoLancamentosTable::configure($table)
            ->headerActions([
                AssociateAction::make()
                    ->label('Vincular custos')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query
                        ->whereNull('resultado_periodo_id')
                        ->where('veiculo_id', $this->ownerRecord->veiculo_id)
                        ->orderByDesc('data_negociacao'))
                    ->recordTitle(fn (ManutencaoLancamento $record): string => sprintf(
                        '%s | %s | %s | R$ %s',
                        Carbon::parse($record->data_negociacao)->format('d/m/Y'),
                        $record->produto,
                        $record->ordem_servico_id ? 'OS #'.$record->ordem_servico_id : 'Sem OS',
                        number_format($record->valor_total_centavos / 100, 2, ',', '.')
                    ))
                    ->recordSelectSearchColumns(['produto', 'nr_unico', 'nr_os_nf'])
                    ->multiple(),
            ])
            ->toolbarActions([
                DissociateBulkAction::make()
                    ->label('Desvincular do resultado'),
            ]);
    }
}
