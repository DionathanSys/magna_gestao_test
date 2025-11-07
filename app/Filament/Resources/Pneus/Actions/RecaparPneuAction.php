<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Enum\Pneu\StatusPneuEnum;
use App\Models;
use App\Services;
use Filament\Actions\Action;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class RecaparPneuAction
{
    public static function make(): Action
    {
        return Action::make('recapar')
            ->label('Registrar Recapagem')
            ->color('info')
            ->action(function (Action $action, Get $get) {
                
                $data = self::mutateDataRecap($get('recap') ?? []);
                $service = new Services\Pneus\PneuService();
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
