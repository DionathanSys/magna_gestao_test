<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Filament\Tables\SelectTableViagem;
use App\Models\DocumentoFrete;
use App\Models\ViagemBugio;
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

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
                    ->tableArguments(function (ViagemBugio $record): array {
                        return [
                            'veiculo_id' => $record->veiculo_id,
                        ];
                    })
            ])
            ->action(function (array $data, $record) {
                $record->viagem_id = $data['viagem_id'];
                $record->save();
            });
    }
}
