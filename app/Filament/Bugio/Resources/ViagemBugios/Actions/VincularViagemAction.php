<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Filament\Tables\SelectTableViagem;
use App\Models\DocumentoFrete;
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Select;

class VincularViagemAction
{
    public static function make(): Action
    {
        return Action::make('vincular_viagem')
            ->label('Vincular Viagem')
            ->schema([
                ModalTableSelect::make('viagem_id')
                    ->relationship('viagem', 'id')
                    ->tableConfiguration(SelectTableViagem::class)
            ])
            ->action(function (array $data, $record) {
                dd($data, $record);
            });
    }
}
