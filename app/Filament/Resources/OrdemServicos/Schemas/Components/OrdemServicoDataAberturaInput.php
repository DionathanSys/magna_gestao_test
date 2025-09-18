<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\Services\Veiculo\VeiculoService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;

class OrdemServicoDataAberturaInput
{
    public static function make($column = 'data_inicio'): TextInput
    {
        return TextInput::make($column)
            ->label('Data de Abertura')
            ->required()
            ->columnSpan([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ]);
    }
}
