<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Filament\Resources\OrdemServicos\Schemas\ItemOrdemServicoForm;
use Filament\Actions\Action;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;


class DesvincularRegistroAction
{
    public static function make(): Action
    {
        return Action::make('desvincular_periodo')
            ->label('Desvincular Período')
            ->icon(Heroicon::XMark)
            ->color('warning')
            ->requiresConfirmation()
            ->action(function ($record) {
                $record->update(['resultado_periodo_id' => null]);
                notify::success(mensagem: 'Abastecimento desvinculado com sucesso!');
            });
    }
}
