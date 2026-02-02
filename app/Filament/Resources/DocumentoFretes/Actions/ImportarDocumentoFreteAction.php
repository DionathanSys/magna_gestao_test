<?php

namespace App\Filament\Resources\DocumentoFretes\Actions;

use App\Enum\Frete\TipoRelatorioDocumentoFreteEnum;
use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessarDocumentoFreteJob;
use Filament\Actions\Action;
use App\Services;
use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportarDocumentoFreteAction
{
    public static function make(): Action
    {
        return Action::make('importar-documento-frete')
            ->label('Importar Documento Frete')
            ->tooltip('Importar um novo Documento de Frete BRF')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('documento_frete')
                    ->label('Documento Frete')
                    ->required(),
                Select::make('tipo_documento')
                    ->label('Tipo de Documento')
                    ->native(false)
                    ->options(TipoRelatorioDocumentoFreteEnum::toSelectArray())
                    ->required(),
            ])
            ->action(function(array $data) {
                Log::debug('Iniciando importação de Documento Frete', [
                    'data' => $data,
                ]);

                $configEnum = TipoRelatorioDocumentoFreteEnum::from($data['tipo_documento'])->config();
                $importerClass = $configEnum['class_importer'];
                $fileName = $data['documento_frete'];

                $job = ProcessarDocumentoFreteJob::dispatch($importerClass, $fileName);

                notify::success('Importação de Documento Frete iniciada com sucesso.');
                
                return true;
            });
    }
}
