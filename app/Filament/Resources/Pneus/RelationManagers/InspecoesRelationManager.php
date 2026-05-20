<?php

namespace App\Filament\Resources\Pneus\RelationManagers;

use App\Filament\Resources\PneuInspecoes\PneuInspecaoResource;
use App\Filament\Resources\PneuInspecoes\Tables\PneuInspecoesTable;
use App\Models\Pneu;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class InspecoesRelationManager extends RelationManager
{
    protected static string $relationship = 'inspecoes';

    protected static ?string $relatedResource = PneuInspecaoResource::class;

    public function table(Table $table): Table
    {
        return PneuInspecoesTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Pneu $pneu */
                        $pneu = $this->getOwnerRecord();

                        $data['pneu_id'] = $pneu->id;
                        $data['pneu_ciclo_id'] = $data['pneu_ciclo_id'] ?? $pneu->cicloAtual?->id;

                        return $data;
                    }),
            ]);
    }
}
