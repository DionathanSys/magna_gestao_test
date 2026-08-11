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
                'status' => filled($item['status'] ?? null) ? (string) $item['status'] : 'Status não informado',
                'km_pago' => (float) ($item['km_pago'] ?? 0),
                'recebido_em_humano' => $this->formatarData($item['recebido_em'] ?? null),
                'inicio_humano' => $this->formatarData($item['inicio'] ?? null),
                'duracao_viagem' => $this->formatarDuracaoDesde($item['inicio'] ?? null),
            ])
            ->sortBy('placa')
            ->values()
            ->toArray();
    }

    public function getTotalVeiculosProperty(): int
    {
        return count($this->viagens);
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

    private function formatarDuracaoDesde(?string $value): string
    {
        if (blank($value)) {
            return 'N/A';
        }

        try {
            $inicio = Carbon::parse($value);
            $totalMinutos = max(0, (int) $inicio->diffInMinutes(now()));
            $dias = intdiv($totalMinutos, 1440);
            $horas = intdiv($totalMinutos % 1440, 60);
            $minutos = $totalMinutos % 60;

            if ($dias > 0) {
                return sprintf('%dd %02dh %02dmin', $dias, $horas, $minutos);
            }

            return sprintf('%02dh %02dmin', $horas, $minutos);
        } catch (\Throwable) {
            return 'N/A';
        }
    }
}
