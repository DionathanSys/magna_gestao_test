<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Filament\Resources\OrdemServicos\Schemas\ItemOrdemServicoForm;
use Filament\Actions\Action;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Closure;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;


class EditarServicoOrdemServicoAction
{
    public static function make(int|Closure $recordId): Action
    {
        return Action::make('editar_servico')
            ->label('Editar')
            ->icon('heroicon-o-pencil')
            ->schema(fn(Schema $schema) => ItemOrdemServicoForm::configure($schema))
            ->model(Models\ItemOrdemServico::class)
            ->action(function (array $data, string $model, Action $action) use($recordId): ?Model {
                $service = new Services\ItemOrdemServico\ItemOrdemServicoService();
                $itemOrdemServico = $service->update($recordId, $data);

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
