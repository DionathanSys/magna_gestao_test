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


class ImportarRegistrosAction
{
    public static function make(): Action
    {
        return Action::make('importar_registros')
            ->label('Importar Registros')
            ->icon(Heroicon::ArrowUpOnSquare)
            ->schema(function (Schema $schema): Schema {
                return $schema
                    ->columns(1)
                    ->components([
                        Toggle::make('considerar_periodo')
                            ->label('Considerar Período')
                            ->helperText('Se ativado, apenas os registros dentro do período definido serão importados.')
                            ->default(true),
                    ]);
            })
            ->action(function (Models\ResultadoPeriodo $record, array $data) {
                $service = new Services\ResultadoPeriodo\ResultadoPeriodoService();
                $service->importarRegistros($record->id, $data['considerar_periodo']);

                // if ($service->hasError()) {
                //     notify::error(mensagem: $service->getMessage());
                //     $action->halt();
                //     return null;
                // }

                notify::success(mensagem: 'Importação concluída com sucesso!');
                // return $itemOrdemServico;
            });
    }


}
