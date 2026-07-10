<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\OrdemServico;
use Filament\Resources\Pages\Page;
use UnitEnum;

class MobileListOrdemServicos extends Page
{
    protected static string $resource = OrdemServicoResource::class;

    protected static ?string $title = 'Ordens de Serviço';

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.resources.ordem-servicos.pages.mobile-list';

    public string $activeTab = 'pendente';

    public function getOrdensServicoProperty()
    {
        $query = OrdemServico::query()
            ->with(['veiculo:id,placa', 'itens.servico:id,descricao'])
            ->whereNotIn('status', [
                StatusOrdemServicoEnum::CONCLUIDO,
                StatusOrdemServicoEnum::CANCELADO,
            ]);

        return match ($this->activeTab) {
            'hoje' => $query->whereDate('data_inicio', today())->orderByDesc('id')->get(),
            'todas' => $query->orderByDesc('id')->get(),
            default => $query->where('status', StatusOrdemServicoEnum::PENDENTE)->orderByDesc('id')->get(),
        };
    }

    public function getHojeCount(): int
    {
        return OrdemServico::query()
            ->whereNotIn('status', [
                StatusOrdemServicoEnum::CONCLUIDO,
                StatusOrdemServicoEnum::CANCELADO,
            ])
            ->whereDate('data_inicio', today())
            ->count();
    }

    public function getPendenteCount(): int
    {
        return OrdemServico::query()
            ->where('status', StatusOrdemServicoEnum::PENDENTE)
            ->count();
    }

    public function getTodasCount(): int
    {
        return OrdemServico::query()
            ->whereNotIn('status', [
                StatusOrdemServicoEnum::CONCLUIDO,
                StatusOrdemServicoEnum::CANCELADO,
            ])
            ->count();
    }

    public function getCreateUrl(): string
    {
        return OrdemServicoResource::getUrl('mobile-create');
    }

    public function getDetailUrl(OrdemServico $ordemServico): string
    {
        return OrdemServicoResource::getUrl('mobile-detail', ['record' => $ordemServico->id]);
    }

    public function getStatusBadgeColor(OrdemServico $ordemServico): string
    {
        return match ($ordemServico->status) {
            StatusOrdemServicoEnum::PENDENTE => 'warning',
            StatusOrdemServicoEnum::EXECUCAO => 'info',
            StatusOrdemServicoEnum::CONCLUIDO => 'success',
            StatusOrdemServicoEnum::CANCELADO => 'danger',
            default => 'gray',
        };
    }
}
