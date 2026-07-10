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
            'todos' => Tab::make(),
            'hoje' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('data_inicio', today()))
                ->badge(Models\OrdemServico::query()->whereDate('data_inicio', today())->count()),
            'pendente' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE)),
            'concluído' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)),
            'abrir_ordem' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_sankhya', Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE))
                ->badge(Models\OrdemServico::query()->where('status_sankhya', Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE)->count())
                ->badgeColor('info'),
            'encerrar_ordem' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('status_sankhya', '!=', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('parceiro_id', null))
                ->badge(Models\OrdemServico::query()
                    ->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('status_sankhya', '!=', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('parceiro_id', null)->count())
                ->badgeColor('info'),
            'Terceiros' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_sankhya', '!=', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('parceiro_id', '!=', null))
                ->badge(Models\OrdemServico::query()->where('status_sankhya', '!=', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('parceiro_id', '!=', null)->count())
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
