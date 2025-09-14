<?php

namespace App\Livewire;

use App\Models;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class PneuResource extends StatsOverviewWidget
{

    public ?Models\Pneu $record = null;

    protected ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 2;

    public function getColumns(): int | array
    {
        return [
            'md' => 6,
        ];
    }

    protected function getStats(): array
    {

        $custoPneuNovo = Models\Pneu::query()
            ->where('id', $this->record?->id)
            ->value('valor');

        $custoRecapagens = Models\Recapagem::query()
            ->where('pneu_id', $this->record?->id)
            ->sum('valor');

        $custoConsertos = Models\Conserto::query()
            ->where('pneu_id', $this->record?->id)
            ->sum('valor');

        $kmRodadoHistorico = Models\HistoricoMovimentoPneu::query()
            ->where('pneu_id', $this->record?->id)
            ->sum('km_percorrido');

        $veiculoAtual = Models\PneuPosicaoVeiculo::query()
            ->where('pneu_id', $this->record?->id)
            ->first();

        if ($veiculoAtual) {
            $kmAtualVeiculo = Models\Veiculo::query()
                ->where('id', $veiculoAtual->veiculo_id)
                ->value('quilometragemAtual');
            $kmRodado = $kmRodadoHistorico + ($kmAtualVeiculo ?? 0);
        } else {
            $kmRodado = $kmRodadoHistorico;
        }

        $custoTotal = $custoPneuNovo + $custoRecapagens + $custoConsertos;
        $custoPorKm = $kmRodado > 0 ? $custoTotal / $kmRodado : 0;


        return [
            Stat::make('Custo Total', 'R$ ' . number_format($custoTotal, 2, ',', '.')),
            Stat::make('Km Rodado', number_format($kmRodado, 0, ',', '.') . ' Km'),
            Stat::make('R$/KM', 'R$ ' . number_format($custoPorKm, 4, ',', '.')),
            Stat::make('Custo Carcaça', 'R$ ' . number_format($custoPneuNovo, 2, ',', '.')),
            Stat::make('Custo Recapagem', 'R$ ' . number_format($custoRecapagens, 2, ',', '.')),
            Stat::make('Custo Consertos', 'R$ ' . number_format($custoConsertos, 2, ',', '.')),
        ];
    }
}
