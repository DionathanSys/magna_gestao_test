<?php

namespace App\Filament\Resources\DocumentoFretes\Actions;

use App\Jobs\VincularViagemDocumentoFrete;
use App\Models;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VincularViagemDocumentoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('vincular-viagem')
            ->label('Vincular Viagem')
            ->icon('heroicon-o-paper-clip')
            ->action(function (Collection $records) {

                $records->each(function (Models\DocumentoFrete $record){
                    if(!$record->documento_transporte){
                        Log::warning('Documento de frete sem viagem vinculada', [
                            'documento_frete_id' => $record->id,
                        ]);
                        return;
                    }
                    VincularViagemDocumentoFrete::dispatch($record->documento_transporte);
                    return;
                });
            })
            ->deselectRecordsAfterCompletion();
    }
}
