<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Icon;
use Filament\Support\Icons\Heroicon;
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
            ->afterStateUpdated(function (Set $set, Field $component, $state) {
                if ($state) {
                    $pneu = Models\Pneu::query()
                        ->where('numero_fogo', $state)
                        ->first();
                    if ($pneu) {
                        $set('recap.pneu_id', $pneu->id);
                        $component->afterLabel([Icon::make(Heroicon::ExclamationTriangle),'Pneu já cadastrado']);
                        notify::alert(
                            titulo: 'Atenção',
                            mensagem: "Já existe um pneu cadastrado com o Nº de Fogo: {$state}",
                        );
                        return;
                    }
                    $component->afterLabel([Icon::make(Heroicon::CheckBadge),'Pneu sem cadastrado']);
                    $set('recap.pneu_id', null);
                }
            });
    }
}
