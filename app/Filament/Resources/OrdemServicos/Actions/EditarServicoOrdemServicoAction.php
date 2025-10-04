<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Filament\Resources\OrdemServicos\Schemas\ItemOrdemServicoForm;
use Filament\Actions\Action;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;


class EditarServicoOrdemServicoAction
{
    public static function make(): Action
    {
        return Action::make('editar_servico')
            ->label('Editar')
            ->icon('heroicon-o-pencil')
            ->fillForm(fn(Models\ItemOrdemServico $record): array => $record->toArray())
            ->schema(fn(Schema $schema) => ItemOrdemServicoForm::configure($schema))
            ->model(Models\ItemOrdemServico::class)
            ->action(function (Models\ItemOrdemServico $record, array $data, string $model, Action $action): ?Model {
                $service = new Services\ItemOrdemServico\ItemOrdemServicoService();
                $itemOrdemServico = $service->update($record->id, $data);

                if ($service->hasError()) {
                    notify::error(mensagem: $service->getMessage());
                    $action->halt();
                    return null;
                }

                notify::success(mensagem: 'Serviço editado com sucesso!');
                return $itemOrdemServico;
            });
    }


}
