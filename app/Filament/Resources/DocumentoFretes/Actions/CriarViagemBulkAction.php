<?php

namespace App\Filament\Resources\DocumentoFretes\Actions;

use App\Enum\Frete\TipoRelatorioDocumentoFreteEnum;
use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessarDocumentoFreteJob;
use Filament\Actions\Action;
use App\Services;
use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CriarViagemBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('criar-viagem')
            ->label('Criar Viagem')
            ->tooltip('Criar viagem a partir do Documento de Frete')
            ->icon(Heroicon::PlusCircle)
            ->action(function($records) {

                $documentoFreteService = new Services\DocumentoFrete\DocumentoFreteService();
                $viagem = $documentoFreteService->createViagemNutrepampaFromDocumentoFrete($records);

                notify::success('Importação de Documento Frete iniciada com sucesso.');
            });
    }
}
