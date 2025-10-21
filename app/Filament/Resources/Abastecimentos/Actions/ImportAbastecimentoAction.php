<?php

namespace App\Filament\Resources\Abastecimentos\Actions;

use App\Services\Import\AbastecimentoImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Log;

class ImportAbastecimentoAction
{
    public static function make(): Action
    {
        return Action::make('importar_abastecimentos')
            ->label('Importar Abastecimentos')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('arquivo')
                    ->label('Relatório Excel')
                    ->disk('public')
                    ->required(),
            ])
            ->action(function (array $data, AbastecimentoImportService $importService): void {
                $filePath = $data['arquivo'];
                $options = [
                    'use_queue' => true,
                    'descricao' => 'Importação de Abastecimentos Sankhya',
                    'batch_size' => 15,
                ];

                $result = $importService->importarAbastecimentos($filePath, $options);

                if ($importService->hasError()) {
                    Log::error('Erro na importação de abastecimentos: ' . $importService->getMessage(), [
                        'metodo' => __METHOD__,
                        'arquivo' => $filePath,
                    ]);
                    \App\Services\NotificacaoService::error($importService->getMessage());
                } else {
                    \App\Services\NotificacaoService::success(
                        "Importação iniciada!"
                    );
                }
            });
    }
}
