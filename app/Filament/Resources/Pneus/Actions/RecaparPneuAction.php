<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;

class RecaparPneuAction
{
    public static function make(): Action
    {
        return Action::make('recapar')
            ->label('Registrar Recapagem')
            ->color('info')
            ->action(function (Action $action, Get $get) {

                $data = self::mutateDataRecap($get('recap') ?? []);
                $service = new Services\Pneus\PneuService;
                $service->recapar($data);

                if ($service->hasError()) {
                    notify::error(titulo: 'Falha no processo de recapagem', mensagem: $service->getMessage());
                    $action->halt();
                }

                notify::success('Recapagem realizada com sucesso.');
                $action->halt();

            });
    }

    private static function mutateDataRecap(array $data): array
    {
        // Normalizar os indices do array, devido conflito de nomes no form
        // entre os campos do pneu e da recapagem
        return [
            'pneu_id' => $data['pneu_id'],
            'valor' => $data['valor_recapagem'],
            'desenho_pneu_id' => $data['desenho_pneu_id_recapagem'],
            'data_recapagem' => $data['data_recapagem'],
        ];
    }
}
