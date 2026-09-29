<?php

namespace App\Observers;

use App\Jobs\Telegram\EnviarOrdemServicoCriadaTelegram;
use App\Models\OrdemServico;
use App\Services\Alertas\TelegramAlertConfigService;

class OrdemServicoObserver
{
    public function created(OrdemServico $ordemServico): void
    {
        $alertConfig = app(TelegramAlertConfigService::class);
        $alertType = TelegramAlertConfigService::ORDEM_SERVICO_CRIADA;

        if (! $alertConfig->isEnabled($alertType)) {
            return;
        }

        foreach ($alertConfig->recipientIds($alertType) as $destinatarioId) {
            EnviarOrdemServicoCriadaTelegram::dispatch(
                (int) $ordemServico->getKey(),
                $destinatarioId,
            )->afterCommit();
        }
    }
}
