<?php

namespace App\Filament\Resources\Agendamentos\Pages;

use App\Enum;
use App\Filament\Resources\Agendamentos\AgendamentoResource;
use App\Filament\Widgets\AgendamentoStats;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ListAgendamentos extends ListRecords
{
    protected static string $resource = AgendamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('operacao')
                ->label('Operação')
                ->icon('heroicon-o-queue-list')
                ->url(AgendamentoResource::getUrl('operacao')),
            Action::make('mobile')
                ->label('Mobile')
                ->icon('heroicon-o-device-phone-mobile')
                ->url(AgendamentoResource::getUrl('mobile-operacao')),
            CreateAction::make()
                ->label('Agendamento')
                ->icon('heroicon-o-plus')
                ->using(function (array $data, string $model): Models\Agendamento {
                    $service = new Services\Agendamento\AgendamentoService;
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
            'historico' => Tab::make('Histórico'),
            'abertos' => Tab::make('Abertos')
                ->modifyQueryUsing(fn (Builder $query) => $query->abertos())
                ->badge(Models\Agendamento::query()->abertos()->count())
                ->badgeColor('gray'),
            'execucao' => Tab::make('Em Execução')
                ->modifyQueryUsing(fn (Builder $query) => $query->emExecucao())
                ->badge(Models\Agendamento::query()->emExecucao()->count())
                ->badgeColor('info'),
            'sem-data' => Tab::make('Sem Data')
                ->modifyQueryUsing(
                    fn (Builder $query) => $query
                        ->pendentes()
                        ->semData()
                )
                ->badge(Models\Agendamento::query()
                    ->pendentes()
                    ->semData()
                    ->count())
                ->badgeColor('info'),
            'checklist' => Tab::make('Checklist')
                ->modifyQueryUsing(
                    fn (Builder $query) => $query
                        ->checklist()
                        ->abertos()
                )
                ->badge(Models\Agendamento::query()
                    ->checklist()
                    ->abertos()
                    ->count())
                ->badgeColor('info'),
            'hoje' => Tab::make('Hoje')
                ->modifyQueryUsing(fn (Builder $query) => $query->agendadosPara(now()->toDateString()))
                ->badge(Models\Agendamento::query()
                    ->agendadosPara(now()->toDateString())
                    ->count())
                ->badgeColor('info'),
            'amanha' => Tab::make('Amanhã')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->pendentes()
                    ->agendadosPara(now()->addDay()->toDateString())
                )
                ->badge(Models\Agendamento::query()
                    ->pendentes()
                    ->agendadosPara(now()->addDay()->toDateString())
                    ->count())
                ->badgeColor('info'),
            'semana' => Tab::make('Semana')
                ->modifyQueryUsing(
                    fn (Builder $query) => $query
                        ->pendentes()
                        ->entreDatas(now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString())
                )
                ->badge(Models\Agendamento::query()
                    ->pendentes()
                    ->entreDatas(now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString())
                    ->count()),
            'atrasados' => Tab::make('Atrasados')
                ->modifyQueryUsing(
                    fn (Builder $query) => $query
                        ->pendentes()
                        ->atrasados()
                )
                ->badge(Models\Agendamento::query()
                    ->pendentes()
                    ->atrasados()
                    ->count())
                ->badgeColor('info'),
            'concluidos' => Tab::make('Concluídos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO))
                ->badge(Models\Agendamento::query()->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)->count())
                ->badgeColor('success'),
            'cancelados' => Tab::make('Cancelados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CANCELADO))
                ->badge(Models\Agendamento::query()->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CANCELADO)->count())
                ->badgeColor('danger'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        $lastActiveTab = session('agendamentos_last_active_tab');

        if ($lastActiveTab && array_key_exists($lastActiveTab, $this->getTabs())) {
            return $lastActiveTab;
        }

        return 'hoje';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AgendamentoStats::class,
        ];
    }

    public function updatedActiveTab(): void
    {
        session(['agendamentos_last_active_tab' => $this->activeTab]);
    }
}
