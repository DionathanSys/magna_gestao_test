<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Jobs\VincularViagemDocumentoFrete;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VincularViagemDocumentoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('vincular-documento')
            ->label('Vincular Documento')
            ->icon('heroicon-o-paper-clip')
            ->action(function (Collection $records) {

                $records->each(function (Models\Viagem $record){
                    if(!$record->documento_transporte){
                        Log::warning('Viagem sem documento de transporte', [
                            'viagem_id' => $record->id,
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
