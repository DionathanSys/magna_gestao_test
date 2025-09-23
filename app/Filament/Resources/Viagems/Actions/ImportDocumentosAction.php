<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Services\Import\ViagemImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;

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
                    ->required(),

                Toggle::make('usar_fila')
                    ->label('Processar em segundo plano')
                    ->default(true),
            ])
            ->action(function (array $data, ViagemImportService $importService): void {
                ds($data)->label('Dados do Formulário de Importação')->blue();
                $filePath = $data['arquivo'];
                $options = [
                    'use_queue' => $data['usar_fila'],
                    'batch_size' => 100,
                ];

                $result = $importService->importarViagens($filePath, $options);

                if ($importService->hasError()) {
                    \App\Services\NotificacaoService::error($importService->getMessage());
                } else {
                    \App\Services\NotificacaoService::success(
                        "Importação iniciada! {$result['success_rows']} registros processados."
                    );
                }
            });
    }
}
