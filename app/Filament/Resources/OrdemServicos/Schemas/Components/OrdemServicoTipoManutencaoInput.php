<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\Enum;
use Filament\Forms\Components\Select;

class OrdemServicoTipoManutencaoInput
{
    public static function make($column = 'tipo_manutencao'): Select
    {
        return Select::make($column)
            ->label('Tipo de Manutenção')
            ->columnSpan(2)
            ->options(Enum\OrdemServico\TipoManutencaoEnum::toSelectArray())
            ->required()
            ->default(Enum\OrdemServico\TipoManutencaoEnum::CORRETIVA->value)
            ->columnSpan([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ]);
    }
}
