<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Enum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListOrdemServicos extends ListRecords
{
    protected static string $resource = OrdemServicoResource::class;

    // Habilita a persistência da aba ativa no localStorage
    protected bool $persistTabInLocalStorage = true;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('OS')
                ->icon('heroicon-o-plus')
                ->modalWidth(Width::FourExtraLarge)
                ->before(function (CreateAction $action, array $data) {
                    $veiculo = Models\Veiculo::with('kmAtual')->find($data['veiculo_id']);
                    if (($veiculo->kmAtual->quilometragem ?? 0) > $data['quilometragem']) {
                        notify::error('A quilometragem informada deve ser maior ou igual à quilometragem atual do veículo.');
                        $action->halt();
                    }
                })
                ->mutateDataUsing(function (array $data): array {
                    $data['created_by'] = Auth::user()->id;
                    $data['status'] = Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE;
                    $data['status_sankhya'] = Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE;

                    return $data;
                })
                ->successRedirectUrl(fn (Models\OrdemServico $record): string => OrdemServicoResource::getUrl('custom', ['record' => $record->getKey()])),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query),
            'hoje' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parceiro_id')->whereDate('data_inicio', today()))
                ->badge(Models\OrdemServico::query()->whereNull('parceiro_id')->whereDate('data_inicio', today())->count()),
            'pendente' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parceiro_id')->whereIn('status', [
                    Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE,
                    Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO,
                ])),
            'concluído' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parceiro_id')->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)),
            'abrir_ordem' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parceiro_id')->where('status_sankhya', Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE))
                ->badge(Models\OrdemServico::query()->whereNull('parceiro_id')->where('status_sankhya', Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE)->count())
                ->badgeColor('info'),
            'encerrar_ordem' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('status_sankhya', '!=', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->whereNull('parceiro_id'))
                ->badge(Models\OrdemServico::query()
                    ->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('status_sankhya', '!=', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->whereNull('parceiro_id')->count())
                ->badgeColor('info'),
            'Terceiros' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('parceiro_id')->whereIn('status', [
                    Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE,
                    Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO,
                ]))
                ->badge(Models\OrdemServico::query()->whereNotNull('parceiro_id')->whereIn('status', [
                    Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE,
                    Enum\OrdemServico\StatusOrdemServicoEnum::EXECUCAO,
                ])->count())
                ->badgeColor('danger'),

        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        $lastActiveTab = session('ordem_servicos_last_active_tab');

        if ($lastActiveTab && array_key_exists($lastActiveTab, $this->getTabs())) {
            return $lastActiveTab;
        }

        return 'pendente';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function updatedActiveTab(): void
    {
        session(['ordem_servicos_last_active_tab' => $this->activeTab]);
    }
}
