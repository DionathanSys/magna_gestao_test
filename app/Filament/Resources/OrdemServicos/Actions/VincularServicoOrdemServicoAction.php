<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Filament\Resources\OrdemServicos\Schemas\ItemOrdemServicoForm;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class VincularServicoOrdemServicoAction
{
    public static function make(Models\OrdemServico|int|null $ordemServico = null): Action
    {
        return Action::make('vincular_servico')
            ->label('Adicionar Serviço')
            ->icon('heroicon-o-plus')
            ->schema(fn (Schema $schema) => ItemOrdemServicoForm::configure($schema))
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('vincularOutro', arguments: ['another' => true]),
            ])
            ->modalSubmitActionLabel('Vincular')
            ->action(function (Action $action, Schema $form, array $data, array $arguments) use ($ordemServico): ?Model {
                $record = static::resolveOrdemServico($ordemServico, $action->getRecord());

                if (! $record instanceof Models\OrdemServico) {
                    notify::error(mensagem: 'Ordem de servico nao encontrada para vincular o item.');
                    $action->halt();

                    return null;
                }

                $data['ordem_servico_id'] = $record->id;

                $service = new Services\ItemOrdemServico\ItemOrdemServicoService;
                $itemOrdemServico = $service->create($data);

                if ($service->hasError()) {
                    notify::error(mensagem: $service->getMessage());
                    $action->halt();

                    return null;
                }

                notify::success(mensagem: 'Serviço vinculado com sucesso!');

                if ($arguments['another'] ?? false) {
                    $form->fill([
                        'servico_id' => null,
                        'controla_posicao' => false,
                        'posicao' => null,
                        'observacao' => null,
                    ]);

                    $action->halt();
                }

                return $itemOrdemServico;
            });
    }

    protected static function resolveOrdemServico(Models\OrdemServico|int|null $ordemServico, ?Model $record): ?Models\OrdemServico
    {
        if ($ordemServico instanceof Models\OrdemServico) {
            return $ordemServico;
        }

        if (is_int($ordemServico)) {
            return Models\OrdemServico::query()->find($ordemServico);
        }

        return $record instanceof Models\OrdemServico ? $record : null;
    }
}
