<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models;
use App\Services\Carga\CargaService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

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
                    ->relationship('integrados', 'nome')
                    ->searchable(['codigo', 'nome'])
                    ->getOptionLabelFromRecordUsing(fn(Models\Integrado $record) => "{$record->codigo} {$record->nome}")
                    ->required(),
            ])
            ->action(function (Models\Viagem $record, array $data) {
                $integrado = Models\Integrado::findOrFail($data['integrado_id']);
                $carga = (new CargaService())->gerarOuComplementar($integrado, $record);

                if (! $carga) {
                    Notification::make()
                        ->warning()
                        ->title('Nenhuma carga criada')
                        ->body('A viagem já possui cargas pendentes de complemento e nenhuma nova carga foi criada automaticamente.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Carga incluída com sucesso!')
                    ->body('A carga foi adicionada ou complementada sem duplicação.')
                    ->send();
            });
    }
}
