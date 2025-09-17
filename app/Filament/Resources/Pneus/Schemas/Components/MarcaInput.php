<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class MarcaInput
{
    public static function make(): Select
    {
        return Select::make('marca')
            ->searchable()
            ->options(collect(db_config('config-pneu.marcas_pneu', []))->mapWithKeys(fn($item) => [$item => $item])->toArray());
    }
}
