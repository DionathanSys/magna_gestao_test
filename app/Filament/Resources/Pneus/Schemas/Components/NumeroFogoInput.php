<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\TextInput;

class NumeroFogoInput
{
    public static function make(): TextInput
    {
        return TextInput::make('numero_fogo')
            ->label('Nº de Fogo')
            ->required()
            ->numeric()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state) {
                if ($state) {
                    $pneu = Models\Pneu::query()
                        ->where('numero_fogo', $state)
                        ->first();
                    if ($pneu) {
                        notify::alert(
                            titulo: 'Atenção',
                            mensagem: "Já existe um pneu cadastrado com o Nº de Fogo: {$state}",
                        );
                    }
                }
            });
    }
}
