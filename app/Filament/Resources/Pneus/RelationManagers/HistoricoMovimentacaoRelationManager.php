<?php

namespace App\Filament\Resources\Pneus\RelationManagers;

use App\Filament\Resources\HistoricoMovimentoPneus\HistoricoMovimentoPneuResource;
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
                CreateAction::make(),
            ]);
    }

}
