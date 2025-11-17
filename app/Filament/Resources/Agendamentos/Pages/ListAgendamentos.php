<?php

namespace App\Filament\Resources\Agendamentos\Pages;

use App\{Models, Services, Enum};
use App\Services\NotificacaoService as notify;
use Illuminate\Support\Arr;
use App\Filament\Resources\Agendamentos\AgendamentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAgendamentos extends ListRecords
{
    protected static string $resource = AgendamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Agendamento')
                ->icon('heroicon-o-plus')
                ->using(function (array $data, string $model): Models\Agendamento {
                    $service = new Services\Agendamento\AgendamentoService();
                    $agendamento = $service->create($data);

                    if ($service->hasError()) {
                        notify::error(mensagem: $service->getMessage());
                        $this->halt();
                    }

                    return $agendamento;
                }),
        ];
    }

    protected function preserveFormDataWhenCreatingAnother(array $data): array
    {
        return Arr::only($data, ['veiculo_id', 'data_agendamento', 'data_limite', 'parceiro_id']);
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make(),
            'Em Execução' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO))
                ->badge(Models\Agendamento::query()->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO)->count())
                ->badgeColor('info'),
            'Sem Data' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->where('data_agendamento', null)
                        ->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE)
                )
                ->badge(Models\Agendamento::query()
                    ->where('data_agendamento', null)
                    ->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE)
                    ->count())
                ->badgeColor('info'),
            'Checklist' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->where('servico_id', 184) // ID do serviço de checklist
                        ->whereIn('status', [Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE, Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO])
                )
                ->badge(Models\Agendamento::query()
                    ->where('servico_id', 184)
                    ->whereIn('status', [Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE, Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO])
                    ->count())
                ->badgeColor('info'),
            'Hoje' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->where('data_agendamento', now()->format('Y-m-d')))
                ->badge(Models\Agendamento::query()
                    ->where('data_agendamento', now()->format('Y-m-d'))->count())
                ->badgeColor('info'),
            'Amanhã' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('data_agendamento', now()->addDay()->format('Y-m-d'))
                    ->whereIn('status', [Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE])
                )
                ->badge(Models\Agendamento::query()
                    ->where('data_agendamento', now()->addDay()->format('Y-m-d'))
                    ->whereIn('status', [Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE])
                    ->count())
                ->badgeColor('info'),
            'Semana' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereBetween('data_agendamento', [
                        now()->startOfWeek()->format('Y-m-d'),
                        now()->endOfWeek()->format('Y-m-d')
                    ])->whereIn('status', [Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE])
                )
                ->badge(Models\Agendamento::query()
                    ->whereIn('status', [Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE])
                    ->whereBetween('data_agendamento', [
                        now()->startOfWeek()->format('Y-m-d'),
                        now()->endOfWeek()->format('Y-m-d')
                    ])
                    ->count()),
            'Atrasados' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('data_agendamento', '<', now()->format('Y-m-d'))
                        ->whereIn('status', [Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE])
                )
                ->badge(Models\Agendamento::query()->where('data_agendamento', '<', now()->format('Y-m-d'))
                    ->whereIn('status', [Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE])->count())
                ->badgeColor('info'),
            'Cancelados' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CANCELADO)),


        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {

        return 'Hoje';
    }
}
