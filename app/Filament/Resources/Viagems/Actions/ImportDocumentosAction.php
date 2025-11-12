<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Services\Import\ViagemImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
                Select::make('unidade_negocio')
                    ->label('Unidade de Negócio')
                    ->options([
                        'BRF_CHAPECO'       => 'BRF CHAPECÓ',
                        'BRF_CATANDUVAS'    => 'BRF CATANDUVAS',
                        'BRF_CONCORDIA'     => 'BRF CONCÓRDIA',
                    ])
                    ->default('BRF_CHAPECO')
                    ->required(),
            ])
            ->action(function (array $data, ViagemImportService $importService): void {
                $filePath = $data['arquivo'];
                $options = [
                    'use_queue' => true,
                    'descricao' => 'Importação de Viagens Softlog - BRF',
                    'batch_size' => 15,
                ];

                $result = $importService->importarViagens($filePath, $options, [
                    'unidade_negocio' => $data['unidade_negocio'],
                ]);

                if ($importService->hasError()) {
                    Log::error('Erro na importação de viagens: ' . $importService->getMessage(), [
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
