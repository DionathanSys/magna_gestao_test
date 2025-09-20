<?php

namespace App\Filament\Resources\Veiculos\RelationManagers;

use App\Models;
use App\Enum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ManutencoesRelationManager extends RelationManager
{
    //TODO: Alterar para o relacionamento com os itens agrupados por OS, primeiro criar o resource de itens
    protected static string $relationship = 'manutencoes';

    protected static ?string $relatedResource = OrdemServicoResource::class;

    public function table(Table $table): Table
    {
        return $table
            
            ->headerActions([
            ]);
    }
}
