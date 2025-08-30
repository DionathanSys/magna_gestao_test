<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Enum\Frete\TipoRelatorioDocumentoFreteEnum;
use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessarDocumentoFreteJob;
use Filament\Actions\Action;
use App\Services;
use App\Models;
use App\Services\NotificacaoService as notify;

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;

class EncerrarOrdemServicoAction
{
    public static function make(): Action
    {
        return Action::make('encerrar')
            ->label('Encerrar OS')
            ->icon('heroicon-o-check-circle')
            ->action(function (Models\OrdemServico $record) {
                $service = new Services\OrdemServico\OrdemServicoService();
                $service->encerrarOrdemServico($record);
                if ($service->hasError()) {
                    notify::error(mensagem: $service->getMessage());
                    return;
                }
                notify::success(mensagem: 'Ordem de Serviço encerrada com sucesso!');
            });
    }
}
