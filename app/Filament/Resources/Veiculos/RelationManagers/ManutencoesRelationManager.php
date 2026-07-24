<?php

namespace App\Filament\Resources\Veiculos\RelationManagers;

use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ManutencoesRelationManager extends RelationManager
{
    // TODO: Alterar para o relacionamento com os itens agrupados por OS, primeiro criar o resource de itens
    protected static string $relationship = 'manutencoes';

    protected static ?string $relatedResource = OrdemServicoResource::class;

    public function table(Table $table): Table
    {
        return $table

            ->headerActions([
            ]);
    }
}
