<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models\PneuMedida;
use Filament\Forms\Components\Select;

class MedidaInput
{
    public static function make(): Select
    {
        return Select::make('pneu_medida_id')
            ->label('Medida')
            ->searchable()
            ->preload()
            ->required()
            ->options(PneuMedida::query()->where('ativo', true)->orderBy('codigo')->pluck('codigo', 'id')->toArray());
    }
}
