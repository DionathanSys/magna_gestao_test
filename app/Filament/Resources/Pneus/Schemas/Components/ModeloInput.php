<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ModeloInput
{
    public static function make(): Select

    {
        return Select::make('modelo')
            ->searchable()
            ->options(db_config('config-pneu.modelos_pneu', []));
    }
}
