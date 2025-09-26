<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Services\Import\ViagemImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Log;

class ImportDocumentosAction
{
    public static function make(): Action
    {
        return Action::make('importar_documentos')
            ->label('Importar Documentos')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('arquivo')
                    ->label('Relatório Excel')
                    ->disk('public')
                    ->required(),

                Toggle::make('usar_fila')
                    ->label('Processar em segundo plano')
                    ->default(true),
            ])
            ->action(function (array $data, ViagemImportService $importService): void {
                $filePath = $data['arquivo'];
                $options = [
                    'use_queue' => $data['usar_fila'],
                    'batch_size' => 5,
                ];
                Log::debug('Iniciando importação de viagens via ação Filament', [
                    'file_path' => $filePath,
                    'options' => $options
                ]);
                $result = $importService->importarViagens($filePath, $options);

                if ($importService->hasError()) {
                    \App\Services\NotificacaoService::error($importService->getMessage());
                } else {
                    \App\Services\NotificacaoService::success(
                        "Importação iniciada!"
                    );
                }
            });
    }
}
