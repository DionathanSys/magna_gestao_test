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
                
                $service = new Services\Pneus\PneuService();
                $service->recapar($get('recap'));

                if ($service->hasError()) {
                    notify::error(titulo: 'Falha no processo de recapagem', mensagem: $service->getMessage());
                    $action->halt();
                }

                notify::success('Recapagem realizada com sucesso.');
            });
    }
}
