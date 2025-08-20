<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

class NovaCargaAction
{
    public static function make(): Action
    {
        return Action::make('nova-carga')
            ->label('Carga')
            ->icon('heroicon-o-plus')
            ->modalSubmitAction(fn(Action $action) => $action->label('Adicionar Carga'))
            ->schema([
                Select::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('carga.integrado', 'nome')
                    ->searchable(['codigo', 'nome'])
                    ->getOptionLabelFromRecordUsing(fn(Models\Integrado $record) => "{$record->codigo} {$record->nome}")
                    ->required(),
            ])
            ->action(fn(Models\Viagem $record, array $data) => Services\CargaService::incluirCargaViagem($data['integrado_id'], $record))
            ->after(fn() => notify::success('Carga incluída com sucesso!', 'A carga foi adicionada à viagem.'));
    }
}
