<?php

namespace App\Filament\Pages;

use App\Services\WebScraper\WebScraperViagemAtualCache;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use UnitEnum;

class DashboardViagensAtuais extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected string $view = 'filament.pages.dashboard-viagens-atuais';

    protected static ?string $navigationLabel = 'Viagens em Tempo Real';

    protected static ?string $title = 'Dashboard de Viagens em Tempo Real';

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    public array $viagens = [];

    public function mount(WebScraperViagemAtualCache $cache): void
    {
        $this->carregarDados($cache);
    }

    public function carregarDados(?WebScraperViagemAtualCache $cache = null): void
    {
        $cache ??= app(WebScraperViagemAtualCache::class);

        $this->viagens = collect($cache->all())
            ->map(fn (array $item): array => [
                ...$item,
                'placa' => $item['placa_normalizada'] ?: ($item['veiculo'] ?? 'Sem placa'),
                'recebido_em_humano' => $this->formatarData($item['recebido_em'] ?? null),
                'inicio_humano' => $this->formatarData($item['inicio'] ?? null),
                'minutos_desde_atualizacao' => $this->minutosDesde($item['recebido_em'] ?? null),
                'diferenca_km' => round(((float) ($item['km_sugerido'] ?? 0)) - ((float) ($item['km_pago'] ?? 0)), 2),
            ])
            ->sortBy('placa')
            ->values()
            ->toArray();
    }

    public function getTotalVeiculosProperty(): int
    {
        return count($this->viagens);
    }

    public function getTotalKmPagoProperty(): float
    {
        return round(collect($this->viagens)->sum('km_pago'), 2);
    }

    public function getTotalKmSugeridoProperty(): float
    {
        return round(collect($this->viagens)->sum('km_sugerido'), 2);
    }

    public function getViagemMaisRecenteProperty(): ?array
    {
        return collect($this->viagens)
            ->sortByDesc(fn (array $item): int => strtotime((string) ($item['recebido_em'] ?? '')) ?: 0)
            ->first();
    }

    private function formatarData(?string $value): string
    {
        if (blank($value)) {
            return 'N/A';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function minutosDesde(?string $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        try {
            return (int) Carbon::parse($value)->diffInMinutes(now());
        } catch (\Throwable) {
            return null;
        }
    }
}
