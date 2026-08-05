<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Resources\Parceiros\ParceiroResource;
use App\Models\OrdemServico;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class CriarOrdemTerceiroAction
{
    public static function make(?OrdemServico $ordemServico = null): Action
    {
        return Action::make('criar_ordem_terceiro')
            ->label('Enviar para Terceiro')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('warning')
            ->modalHeading('Criar OS para terceiro')
            ->modalDescription('Selecione os serviços pendentes que devem sair desta OS e ir para uma nova OS do parceiro.')
            ->modalSubmitActionLabel('Criar OS')
            ->visible(fn (?Model $record = null): bool => self::resolveOrdemServico($ordemServico, $record)?->itens()
                ->where('status', StatusOrdemServicoEnum::PENDENTE)
                ->exists() ?? false)
            ->form(fn (?Model $record = null): array => self::form(self::resolveOrdemServico($ordemServico, $record)))
            ->action(function (array $data, Action $action, ?Model $record = null) use ($ordemServico): void {
                $record = self::resolveOrdemServico($ordemServico, $record);

                if (! $record) {
                    notify::error(mensagem: 'Ordem de Serviço não encontrada.');
                    $action->halt();

                    return;
                }

                try {
                    $novaOrdem = self::criarOrdemTerceiro($record, $data);

                    notify::success(
                        mensagem: 'OS #'.$novaOrdem->id.' criada para terceiro com sucesso.'
                    );
                } catch (Throwable $exception) {
                    notify::error(mensagem: $exception->getMessage());
                    $action->halt();
                }
            });
    }

    private static function form(?OrdemServico $ordemServico): array
    {
        return [
            Select::make('parceiro_id')
                ->label('Parceiro')
                ->relationship('parceiro', 'nome')
                ->model(OrdemServico::class)
                ->createOptionForm(fn ($schema) => ParceiroResource::form($schema))
                ->searchable()
                ->preload()
                ->native(false)
                ->required(),
            CheckboxList::make('item_ids')
                ->label('Serviços pendentes')
                ->options(fn (): array => $ordemServico?->itens()
                    ->where('status', StatusOrdemServicoEnum::PENDENTE)
                    ->with('servico:id,codigo,descricao')
                    ->orderBy('id')
                    ->get()
                    ->mapWithKeys(fn ($item): array => [
                        $item->id => trim(($item->servico?->codigo ? $item->servico->codigo.' - ' : '').($item->servico?->descricao ?? 'Serviço não informado')),
                    ])
                    ->all() ?? [])
                ->columns(1)
                ->required(),
        ];
    }

    private static function criarOrdemTerceiro(OrdemServico $ordemServico, array $data): OrdemServico
    {
        $itemIds = array_values(array_unique($data['item_ids'] ?? []));

        if ($itemIds === []) {
            throw new InvalidArgumentException('Selecione ao menos um serviço pendente.');
        }

        return DB::transaction(function () use ($ordemServico, $data, $itemIds): OrdemServico {
            $ordemServico = OrdemServico::query()
                ->whereKey($ordemServico->id)
                ->lockForUpdate()
                ->firstOrFail();

            $itens = $ordemServico->itens()
                ->where('status', StatusOrdemServicoEnum::PENDENTE)
                ->whereIn('id', $itemIds)
                ->lockForUpdate()
                ->get();

            if ($itens->count() !== count($itemIds)) {
                throw new InvalidArgumentException('A seleção possui serviços que não estão pendentes ou não pertencem a esta OS.');
            }

            $novaOrdem = OrdemServico::query()->create([
                'veiculo_id' => $ordemServico->veiculo_id,
                'quilometragem' => $ordemServico->quilometragem,
                'tipo_manutencao' => $ordemServico->tipo_manutencao,
                'data_inicio' => now(),
                'status' => StatusOrdemServicoEnum::PENDENTE,
                'status_sankhya' => StatusOrdemServicoEnum::PENDENTE,
                'parceiro_id' => $data['parceiro_id'],
                'veiculo_na_oficina' => $ordemServico->veiculo_na_oficina,
                'created_by' => Auth::id(),
            ]);

            $ordemServico->itens()
                ->whereIn('id', $itens->pluck('id'))
                ->update(['ordem_servico_id' => $novaOrdem->id]);

            return $novaOrdem;
        });
    }

    private static function resolveOrdemServico(?OrdemServico $ordemServico, ?Model $record): ?OrdemServico
    {
        if ($ordemServico instanceof OrdemServico) {
            return $ordemServico;
        }

        return $record instanceof OrdemServico ? $record : null;
    }
}
