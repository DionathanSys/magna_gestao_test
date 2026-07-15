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

    protected bool $persistTabInLocalStorage = true;

    public function getTabs(): array
    {
        return [
            'abertas' => Tab::make('Abertas')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('status', [
                    StatusOrdemServicoEnum::CONCLUIDO->value,
                    StatusOrdemServicoEnum::CANCELADO->value,
                ]))
                ->badge(OrdemServico::query()->whereNull('parceiro_id')->whereNotIn('status', [
                    StatusOrdemServicoEnum::CONCLUIDO->value,
                    StatusOrdemServicoEnum::CANCELADO->value,
                ])->count()),
            'encerradas' => Tab::make('Encerradas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusOrdemServicoEnum::CONCLUIDO->value))
                ->badge(OrdemServico::query()->whereNull('parceiro_id')->where('status', StatusOrdemServicoEnum::CONCLUIDO->value)->count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'abertas';
    }
}
