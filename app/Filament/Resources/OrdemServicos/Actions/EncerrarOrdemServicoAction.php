<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Enum\Frete\TipoRelatorioDocumentoFreteEnum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessarDocumentoFreteJob;
use Filament\Actions\Action;
use App\Services;
use App\Models;
use App\Services\NotificacaoService as notify;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EncerrarOrdemServicoAction
{
    public static function make(): Action
    {
        return Action::make('encerrar')
            ->label('Encerrar OS')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->action(function (Models\OrdemServico $record, Action $action) {
                $service = new Services\OrdemServico\OrdemServicoService();
                $service->encerrarOrdemServico($record);
                if ($service->hasError()) {
                    notify::error(mensagem: $service->getMessage());
                    $action->cancel();
                    return;
                }
                notify::success(mensagem: 'Ordem de Serviço encerrada com sucesso!');
            })
            ->successRedirectUrl(fn (Model $record): string => OrdemServicoResource::getUrl());
    }
}
