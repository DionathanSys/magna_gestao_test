<?php

namespace App\Filament\Resources\Pneus\RelationManagers;

use App\Filament\Resources\HistoricoMovimentoPneus\HistoricoMovimentoPneuResource;
use App\Models\Pneu;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class HistoricoMovimentacaoRelationManager extends RelationManager
{
    protected static string $relationship = 'historicoMovimentacao';

    protected static ?string $relatedResource = HistoricoMovimentoPneuResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->defaultGroup('pneu.ciclo_vida')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Pneu $pneu */
                        $pneu = $this->getOwnerRecord();

                        $data['ciclo_vida'] = $data['ciclo_vida'] ?? $pneu->ciclo_vida;
                        $data['pneu_ciclo_id'] = $pneu->cicloAtual?->id;
                        $data['tipo_evento'] = $data['tipo_evento'] ?? 'REMOCAO';

                        return $data;
                    }),
            ]);
    }
}
