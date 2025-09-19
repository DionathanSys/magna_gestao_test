<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Models;
use App\Enum;
use App\Services\NotificacaoService as notify;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class ListOrdemServicos extends ListRecords
{
    protected static string $resource = OrdemServicoResource::class;

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
                    return $data;
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make(),
            'hoje' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('data_inicio', '>', today()))
                ->badge(Models\OrdemServico::query()->whereDate('data_inicio', '>', today())->count()),
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
                                                            ->where('status_sankhya', '!=',Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO))
                                                            ->badge(Models\OrdemServico::query()
                                                                ->where('status', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                                                                ->where('status_sankhya', '!=',Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)->count())
                                                            ->badgeColor('info'),
            'Terceiros' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_sankhya', '!=', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('parceiro_id', '!=', null))
                ->badge(Models\OrdemServico::query()->where('status_sankhya', '!=', Enum\OrdemServico\StatusOrdemServicoEnum::CONCLUIDO)
                    ->where('parceiro_id', '!=', null)->count())
                    ->badgeColor('danger'),

        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        if(Auth::user()->name == 'Angelica'){
            return 'abrir_ordem';
        }

        return 'pendente';

    }
}
