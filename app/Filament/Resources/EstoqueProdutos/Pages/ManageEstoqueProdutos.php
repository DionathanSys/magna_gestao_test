<?php

namespace App\Filament\Resources\EstoqueProdutos\Pages;

use App\Filament\Resources\EstoqueProdutos\EstoqueProdutoResource;
use App\Services\Import\EstoqueImportService;
use App\Services\NotificacaoService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Log;

class ManageEstoqueProdutos extends ManageRecords
{
    protected static string $resource = EstoqueProdutoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importar_listagem_estoque')
                ->label('Importar Estoque')
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    FileUpload::make('arquivo')
                        ->label('Relatório de estoque')
                        ->disk('public')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, EstoqueImportService $importService): void {
                    $importService->importarListagem($data['arquivo'], [
                        'use_queue' => true,
                        'descricao' => 'Importação de listagem de estoque',
                        'batch_size' => 50,
                        'header_row' => 3,
                    ]);

                    $this->notificarResultadoImportacao($importService, $data['arquivo']);
                }),
            Action::make('importar_movimentacao_diaria')
                ->label('Importar Movimentação')
                ->icon('heroicon-o-arrows-right-left')
                ->schema([
                    DatePicker::make('data_movimento')
                        ->label('Data do movimento')
                        ->default(now()->subDay())
                        ->required(),
                    FileUpload::make('arquivo')
                        ->label('Relatório de movimentação diária')
                        ->disk('public')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, EstoqueImportService $importService): void {
                    $importService->importarMovimentacaoDiaria($data['arquivo'], [
                        'use_queue' => true,
                        'descricao' => 'Importação de movimentação diária de estoque',
                        'batch_size' => 50,
                        'header_row' => 3,
                        'data_movimento' => $data['data_movimento'],
                    ]);

                    $this->notificarResultadoImportacao($importService, $data['arquivo']);
                }),
            CreateAction::make(),
        ];
    }

    private function notificarResultadoImportacao(EstoqueImportService $importService, string $filePath): void
    {
        if ($importService->hasError()) {
            Log::error('Erro na importação de estoque', [
                'arquivo' => $filePath,
                'errors' => $importService->getErrors(),
            ]);

            NotificacaoService::error('Falha na importação', $importService->getMessageUser());

            return;
        }

        NotificacaoService::success('Importação iniciada', 'O relatório foi enviado para processamento em fila.');
    }
}
