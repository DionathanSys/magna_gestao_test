<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Models;
use App\Services;
use Filament\Actions\Action;
use App\Services\NotificacaoService as notify;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class RecaparPneuAction
{
    public static function make(): Action
    {
        return Action::make('recapar')
            ->label('Registrar Recapagem')
            ->color('success')
            ->action(function (Action $action, array $data, array $arguments) {
                dd($data, $arguments);
                $data = self::mutateDataRecap($data['recap']);
                $service = new Services\Pneus\PneuService();
                $service->recapar($data);

                if($service->hasError()){
                    notify::error(titulo: 'Erro ao recapar pneu', mensagem: $service->getMessage());
                    $action->halt();
                }

                notify::success('Recapagem realizada com sucesso.');
            });
    }

    private static function mutateDataRecap(array $data): array
    {
        //Normalizar os indices do array, devido conflito de nomes no form
        //entre os campos do pneu e da recapagem
        return [
            'pneu_id'           => $data['pneu_id'],
            'valor'             => $data['valor_recapagem'],
            'desenho_pneu_id'   => $data['desenho_pneu_id_recapagem'],
            'data_recapagem'    => $data['data_recapagem'],
        ];
    }
}
