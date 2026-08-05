<?php

namespace App\Filament\Oficina\Resources\OrdemServicos\Pages;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Oficina\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\OrdemServico;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrdemServicos extends ListRecords
{
    protected static string $resource = OrdemServicoResource::class;

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'oficina' => Tab::make('Oficina')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('veiculo_na_oficina', true))
                ->badge(OrdemServico::query()->whereNull('parceiro_id')->where('veiculo_na_oficina', true)->count())
                ->badgeColor('success'),
            'em_aberto' => Tab::make('Em Aberto')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    StatusOrdemServicoEnum::PENDENTE,
                    StatusOrdemServicoEnum::EXECUCAO,
                ]))
                ->badge(OrdemServico::query()->whereNull('parceiro_id')->whereIn('status', [
                    StatusOrdemServicoEnum::PENDENTE,
                    StatusOrdemServicoEnum::EXECUCAO,
                ])->count())
                ->badgeColor('info'),
            'todos' => Tab::make('Todos'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'oficina';
    }
}
