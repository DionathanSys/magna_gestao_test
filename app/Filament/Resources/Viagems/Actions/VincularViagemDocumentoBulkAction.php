<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Jobs\VincularViagemDocumentoFrete;
use App\Jobs\VincularViagensBatch;
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
            ->fetchSelectedRecords(false)
            ->action(function (Collection $records) {
                $records->chunk(250)->each(function (Collection $chunk) {
                    VincularViagensBatch::dispatch($chunk);
                });
            })
            ->deselectRecordsAfterCompletion();
    }
}
