<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Enum\StatusDiversosEnum;
use App\Filament\Resources\OrdemServicos\Schemas\ItemOrdemServicoForm;
use Filament\Actions\Action;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;


class EncerrarResultadoAction
{
    public static function make(): Action
    {
        return Action::make('encerrar_resultado')
            ->label('Encerrar Resultado')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->action(function ($record) {
                $record->update(['status' => StatusDiversosEnum::ENCERRADO->value]);
                notify::success();
            });
    }
}
