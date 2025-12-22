<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Filament\Tables\SelectDocumentoFrete;
use App\Models\DocumentoFrete;
use App\Models\ViagemBugio;
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Select;

class VincularDocumentoFreteAction
{
    public static function make(): Action
    {
        return Action::make('vincular_documento_frete')
            ->label('Vincular Documento de Frete')
            ->schema([
                ModalTableSelect::make('documento_frete_id')
                    ->relationship('documento', 'id')
                    ->tableConfiguration(SelectDocumentoFrete::class)
                    ->tableArguments(function (ViagemBugio $record): array {
                        return [
                            'veiculo_id' => $record->veiculo_id,
                        ];
                    })
            ])
            ->action(function (array $data, $record) {
                $record->documento_frete_id = $data['documento_frete_id'];
                $record->save();
            });
    }
}
