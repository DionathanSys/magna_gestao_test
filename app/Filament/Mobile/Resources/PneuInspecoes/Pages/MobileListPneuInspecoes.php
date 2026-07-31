<?php

namespace App\Filament\Mobile\Resources\PneuInspecoes\Pages;

use App\Enum\Pneu\ResultadoInspecaoPneuEnum;
use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Filament\Mobile\Resources\PneuInspecoes\PneuInspecaoResource;
use App\Models\PneuInspecao;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class MobileListPneuInspecoes extends Page
{
    use WithPagination;

    protected static string $resource = PneuInspecaoResource::class;

    protected static ?string $title = 'Inspeções de Pneus';

    protected string $view = 'filament.mobile.resources.pneu-inspecoes.pages.mobile-list-pneu-inspecoes';

    public string $activeTab = 'recentes';

    public string $busca = '';

    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function getInspecoesProperty(): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->latest('data_inspecao')
            ->latest('id')
            ->paginate(12);
    }

    public function getRecentesCount(): int
    {
        return PneuInspecao::query()->count();
    }

    public function getHojeCount(): int
    {
        return PneuInspecao::query()->whereDate('data_inspecao', today())->count();
    }

    public function getMonitorarCount(): int
    {
        return PneuInspecao::query()->where('resultado', ResultadoInspecaoPneuEnum::MONITORAR)->count();
    }

    public function getCriticasCount(): int
    {
        return PneuInspecao::query()
            ->whereIn('resultado', [
                ResultadoInspecaoPneuEnum::AGUARDANDO_CONSERTO,
                ResultadoInspecaoPneuEnum::REPROVADO,
                ResultadoInspecaoPneuEnum::CONDENADO,
            ])
            ->count();
    }

    public function getRecapagemCount(): int
    {
        return PneuInspecao::query()
            ->where(function (Builder $query): void {
                $query
                    ->where('apto_recapagem', true)
                    ->orWhere('resultado', ResultadoInspecaoPneuEnum::APTO_RECAPAGEM);
            })
            ->count();
    }

    public function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return 'Sem data';
        }

        return Carbon::parse($value)->format('d/m/Y');
    }

    public function formatNumber(float|int|null $value, int $decimals = 0): string
    {
        if ($value === null) {
            return 'N/A';
        }

        return number_format((float) $value, $decimals, ',', '.');
    }

    public function getTipoColor(PneuInspecao $inspecao): string
    {
        return match ($inspecao->tipo) {
            TipoInspecaoPneuEnum::MOVIMENTACAO, TipoInspecaoPneuEnum::CAMPO => 'info',
            TipoInspecaoPneuEnum::RECEBIMENTO, TipoInspecaoPneuEnum::POS_RECAPAGEM => 'success',
            TipoInspecaoPneuEnum::PRE_RECAPAGEM => 'warning',
            TipoInspecaoPneuEnum::CONDENACAO => 'danger',
            default => 'gray',
        };
    }

    public function getResultadoColor(PneuInspecao $inspecao): string
    {
        return match ($inspecao->resultado) {
            ResultadoInspecaoPneuEnum::APROVADO => 'success',
            ResultadoInspecaoPneuEnum::MONITORAR => 'warning',
            ResultadoInspecaoPneuEnum::APTO_RECAPAGEM => 'info',
            ResultadoInspecaoPneuEnum::AGUARDANDO_CONSERTO, ResultadoInspecaoPneuEnum::REPROVADO => 'danger',
            ResultadoInspecaoPneuEnum::CONDENADO => 'gray',
            default => 'gray',
        };
    }

    public function getMenorSulco(PneuInspecao $inspecao): ?float
    {
        $sulcos = collect([
            $inspecao->sulco_interno,
            $inspecao->sulco_centro,
            $inspecao->sulco_externo,
        ])
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->map(fn ($value): float => (float) $value);

        return $sulcos->isEmpty() ? null : $sulcos->min();
    }

    public function getSulcoColor(PneuInspecao $inspecao): string
    {
        $menorSulco = $this->getMenorSulco($inspecao);

        if ($menorSulco === null) {
            return 'gray';
        }

        return match (true) {
            $menorSulco <= 3 => 'danger',
            $menorSulco <= 5 => 'warning',
            default => 'success',
        };
    }

    protected function baseQuery(): Builder
    {
        return PneuInspecao::query()
            ->with([
                'pneu:id,numero_fogo',
                'ciclo:id,numero,status',
                'veiculo:id,placa',
                'posicaoVeiculo:id,eixo,posicao',
                'parceiro:id,nome',
            ])
            ->when($this->activeTab === 'hoje', fn (Builder $query): Builder => $query->whereDate('data_inspecao', today()))
            ->when($this->activeTab === 'monitorar', fn (Builder $query): Builder => $query->where('resultado', ResultadoInspecaoPneuEnum::MONITORAR))
            ->when($this->activeTab === 'criticas', function (Builder $query): Builder {
                return $query->whereIn('resultado', [
                    ResultadoInspecaoPneuEnum::AGUARDANDO_CONSERTO,
                    ResultadoInspecaoPneuEnum::REPROVADO,
                    ResultadoInspecaoPneuEnum::CONDENADO,
                ]);
            })
            ->when($this->activeTab === 'recapagem', function (Builder $query): Builder {
                return $query->where(function (Builder $query): void {
                    $query
                        ->where('apto_recapagem', true)
                        ->orWhere('resultado', ResultadoInspecaoPneuEnum::APTO_RECAPAGEM);
                });
            })
            ->when(filled($this->busca), function (Builder $query): void {
                $busca = trim($this->busca);

                $query->where(function (Builder $query) use ($busca): void {
                    $query
                        ->where('observacao', 'like', "%{$busca}%")
                        ->orWhereHas('pneu', fn (Builder $query): Builder => $query->where('numero_fogo', 'like', "%{$busca}%"))
                        ->orWhereHas('veiculo', fn (Builder $query): Builder => $query->where('placa', 'like', "%{$busca}%"))
                        ->orWhereHas('parceiro', fn (Builder $query): Builder => $query->where('nome', 'like', "%{$busca}%"));
                });
            });
    }
}
