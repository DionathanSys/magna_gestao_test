<?php

namespace App\Filament\Resources\CompraPedidos\Pages;

use App\Filament\Resources\CompraPedidos\CompraPedidoResource;
use App\Services\Import\CompraPedidoImportService;
use App\Services\NotificacaoService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListCompraPedidos extends ListRecords
{
    protected static string $resource = CompraPedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importar_pedido_compra')
                ->label('Importar Pedido')
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    TextInput::make('numero_pedido')
                        ->label('Número do pedido')
                        ->required()
                        ->maxLength(255),
                    FileUpload::make('arquivo')
                        ->label('Relatório Item Nota/Pedido')
                        ->disk('public')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, CompraPedidoImportService $importService): void {
                    $importService->importarItens($data['arquivo'], [
                        'use_queue' => true,
                        'descricao' => 'Importação de itens de pedido de compra',
                        'batch_size' => 50,
                        'header_row' => 3,
                        'numero_pedido' => $data['numero_pedido'],
                    ]);

                    if ($importService->hasError()) {
                        Log::error('Erro na importação de pedido de compra', [
                            'arquivo' => $data['arquivo'],
                            'errors' => $importService->getErrors(),
                        ]);

                        NotificacaoService::error('Falha na importação', $importService->getMessageUser());

                        return;
                    }

                    NotificacaoService::success('Importação iniciada', 'O relatório foi enviado para processamento em fila.');
                }),
            CreateAction::make(),
        ];
    }
}
