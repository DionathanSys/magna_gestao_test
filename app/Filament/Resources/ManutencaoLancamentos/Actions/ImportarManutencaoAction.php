<?php

namespace App\Filament\Resources\ManutencaoLancamentos\Actions;

use App\Services\Import\ManutencaoImportService;
use App\Services\NotificacaoService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Log;

class ImportarManutencaoAction
{
    public static function make(): Action
    {
        return Action::make('importar_relatorio_manutencao')
            ->label('Importar Relatório ERP')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('arquivo')
                    ->label('Relatório ERP')
                    ->disk('public')
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/csv',
                    ])
                    ->required(),
            ])
            ->action(function (array $data, ManutencaoImportService $importService): void {
                $filePath = $data['arquivo'];
                $options = [
                    'use_queue' => true,
                    'descricao' => 'Importação de custos de manutenção ERP',
                    'batch_size' => 50,
                    'header_row' => 3,
                ];

                $importService->importarLancamentos($filePath, $options);

                if ($importService->hasError()) {
                    Log::error('Erro na importação de manutenção', [
                        'arquivo' => $filePath,
                        'errors' => $importService->getErrors(),
                    ]);

                    NotificacaoService::error('Falha na importação', $importService->getMessageUser());

                    return;
                }

                NotificacaoService::success('Importação iniciada', 'O relatório foi enviado para processamento em fila.');
            });
    }
}
