<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;

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
            ->aboveLabel(fn($state) => [
                $state ?? 'vazio'
            ])
            ->afterStateUpdated(function (Set $set, $state) {
                if ($state) {
                    $pneu = Models\Pneu::query()
                        ->where('numero_fogo', $state)
                        ->first();
                    if ($pneu) {
                        $set('pneu_id', $pneu->id);
                        notify::alert(
                            titulo: 'Atenção',
                            mensagem: "Já existe um pneu cadastrado com o Nº de Fogo: {$state}",
                        );
                    }
                }
            });
    }
}
