<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use App\Enum\StatusDiversosEnum;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ImportarRegistrosAction
{
    public static function make(): Action
    {
        return Action::make('importar_registros')
            ->label('Importar Registros')
            ->icon(Heroicon::ArrowUpOnSquare)
            ->requiresConfirmation()
            ->modalHeading('Buscar e vincular registros')
            ->modalDescription('Somente registros sem vínculo do mesmo veículo serão incluídos. Períodos encerrados não podem ser alterados.')
            ->schema(function (Schema $schema): Schema {
                return $schema
                    ->columns(1)
                    ->components([
                        Toggle::make('considerar_periodo')
                            ->label('Restringir à data do período')
                            ->helperText('Desative para buscar todos os registros sem vínculo do veículo, independentemente da data.')
                            ->default(true),
                    ]);
            })
            ->visible(fn (Models\ResultadoPeriodo $record): bool => $record->status === StatusDiversosEnum::PENDENTE->value)
            ->action(function (Models\ResultadoPeriodo $record, array $data) {
                $service = new Services\ResultadoPeriodo\ResultadoPeriodoService;
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
