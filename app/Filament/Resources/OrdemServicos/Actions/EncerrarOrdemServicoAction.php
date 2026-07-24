<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Models;
use App\Services;
use App\Services\Garantia\GarantiaServicoService;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;

class EncerrarOrdemServicoAction
{
    public static function make(?Models\OrdemServico $ordemServico = null): Action
    {
        return Action::make('encerrar')
            ->label('Encerrar')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Encerrar ordem de serviço')
            ->modalDescription('Confirme o encerramento da OS. Itens pendentes serão concluídos e os demais manterão o status atual.')
            ->form([
                Checkbox::make('encerrar_sankhya')
                    ->label('OS Sankhya Encerrada?')
                    ->default(false),
            ])
            ->action(function (array $data, Action $action, ?Models\OrdemServico $record = null) use ($ordemServico) {
                $record ??= $ordemServico;

                if (! $record) {
                    notify::error(mensagem: 'Ordem de Serviço não encontrada para encerramento.');
                    $action->cancel();

                    return;
                }

                $service = new Services\OrdemServico\OrdemServicoService;
                $service->encerrarOrdemServico($record, (bool) ($data['encerrar_sankhya'] ?? false));

                if ($service->hasError()) {
                    notify::error(mensagem: $service->getMessage());
                    $action->cancel();

                    return;
                }

                notify::success(mensagem: 'Ordem de Serviço encerrada com sucesso!');

                $alertas = app(GarantiaServicoService::class)->alertasDaOrdem($record);

                if ($alertas->isNotEmpty()) {
                    notify::alert(
                        titulo: 'Serviço em garantia',
                        mensagem: $alertas->count().' serviço(s) retornaram dentro do prazo/km de garantia.'
                    );
                }
            });
    }
}
