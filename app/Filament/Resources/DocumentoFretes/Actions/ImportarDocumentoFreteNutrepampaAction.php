<?php

namespace App\Filament\Resources\DocumentoFretes\Actions;

use Filament\Actions\Action;
use App\Services\Import\DocumentoFreteImportService;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Log;

class ImportarDocumentoFreteNutrepampaAction
{
    public static function make(): Action
    {
        return Action::make('importar-documento-frete-nutrepampa')
            ->label('Importar Documento Frete Nutrepampa')
            ->tooltip('Importar Documento Frete Nutrepampa')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('arquivo')
                    ->label('Relatório Excel')
                    ->disk('public')
                    ->required(),
            ])
            ->action(function (array $data, DocumentoFreteImportService $importService) {
                $filePath = $data['arquivo'];
                $options = [
                    'use_queue' => true,
                    'descricao' => 'Importação de Documento Frete Nutrepampa',
                    'batch_size' => 15,
                ];

                Log::debug(__METHOD__.'@'.__LINE__);

                $result = $importService->importarDocumentosNutrepampa($filePath, $options);

                if ($importService->hasError()) {
                    Log::error('Erro na importação de documento frete Nutrepampa: ' . $importService->getMessage(), [
                        'metodo' => __METHOD__,
                        'arquivo' => $filePath,
                    ]);
                    notify::error($importService->getMessage());
                } else {
                    notify::success(
                        "Importação iniciada!"
                    );
                }
                
                return true;
            });
    }
}
