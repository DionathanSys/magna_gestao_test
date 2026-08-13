<?php

namespace App\Filament\Pages;

use App\Enum\ClienteEnum;
use App\Models\Veiculo;
use App\Models\Viagem;
use App\Services\MailInbound\Support\DocumentIdentity;
use App\Services\WebScraper\SascarMovimentoDiarioCache;
use App\Services\WebScraper\WebScraperViagemAtualCache;
use App\Support\Filters\ParsesDateRangeFilter;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use UnitEnum;

class DashboardViagensVeiculos extends Page
{
    use ParsesDateRangeFilter;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected string $view = 'filament.pages.dashboard-viagens-veiculos';

    protected static ?string $navigationLabel = 'Viagens por Veículo';

    protected static ?string $title = 'Dashboard de Viagens por Veículo';

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    public ?array $data = [];

    public array $cards = [];

    private array $viagensAtuais = [];

    private ?string $diaMovimento = null;

    public function mount(): void
    {
        $this->data = $this->getDefaultFilters();
        $this->carregarDados();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Filtros')
                    ->description('As viagens são agrupadas pelo cliente vinculado ao veículo.')
                    ->columns(4)
                    ->columnSpan(12)
                    ->components([
                        DateRangePicker::make('periodo')
                            ->label('Período')
                            ->autoApply()
                            ->firstDayOfWeek(0)
                            ->alwaysShowCalendar()
                            ->required(),

                        Select::make('veiculo_id')
                            ->label('Veículo')
                            ->placeholder('Todos')
                            ->options(fn (): array => Veiculo::query()
                                ->orderBy('placa')
                                ->pluck('placa', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload(),

                        Select::make('cliente')
                            ->label('Cliente')
                            ->placeholder('Todos')
                            ->options(ClienteEnum::toSelectArray())
                            ->searchable()
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function carregarDados(): void
    {
        [$inicio, $fim] = $this->getPeriodoFiltro();
        $dataInicio = $inicio->toDateString();
        $dataFim = $fim->toDateString();
        $this->diaMovimento = $dataFim;
        $this->viagensAtuais = $this->getViagensAtuaisPorVeiculo();

        if ($dataFim < $dataInicio) {
            Notification::make()
                ->title('Período inválido')
                ->body('A data final deve ser maior ou igual à data inicial.')
                ->warning()
                ->send();

            $this->cards = [];

            return;
        }

        $this->cards = Viagem::query()
            ->leftJoin('veiculos', 'veiculos.id', '=', 'viagens.veiculo_id')
            ->select([
                'viagens.veiculo_id',
                DB::raw("COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(veiculos.informacoes_complementares, '$.cliente')), ''), 'Sem cliente') as cliente"),
                DB::raw("COALESCE(veiculos.placa, 'Sem veículo') as placa"),
                DB::raw('COUNT(*) as total_viagens'),
            ])
            ->whereDate('viagens.data_competencia', '>=', $dataInicio)
            ->whereDate('viagens.data_competencia', '<=', $dataFim)
            ->when(
                filled($this->data['veiculo_id'] ?? null),
                fn ($query) => $query->where('viagens.veiculo_id', $this->data['veiculo_id']),
            )
            ->when(
                filled($this->data['cliente'] ?? null),
                fn ($query) => $query->where('veiculos.informacoes_complementares->cliente', $this->data['cliente']),
            )
            ->groupBy('viagens.veiculo_id', 'veiculos.placa')
            ->groupByRaw("COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(veiculos.informacoes_complementares, '$.cliente')), ''), 'Sem cliente')")
            ->orderByDesc('total_viagens')
            ->orderBy('placa')
            ->get()
            ->map(fn ($item): array => [
                'veiculo_id' => $item->veiculo_id,
                'placa' => $item->placa,
                'cliente' => $item->cliente ?: 'Sem cliente',
                'total_viagens' => (int) $item->total_viagens,
                'viagem_atual' => $this->resolverViagemAtual($item->veiculo_id, $item->placa),
                'movimento_diario' => $this->resolverMovimentoDiario($item->veiculo_id, $item->placa),
            ])
            ->toArray();
    }

    public function limparFiltros(): void
    {
        $this->data = $this->getDefaultFilters();
        $this->carregarDados();
    }

    public function gerarPdf(): mixed
    {
        try {
            $this->carregarDados();

            $veiculos = $this->getVeiculosAgrupados();

            if ($veiculos === []) {
                Notification::make()
                    ->title('Sem dados para gerar PDF')
                    ->warning()
                    ->body('Nenhum veículo encontrado com os filtros atuais.')
                    ->send();

                return null;
            }

            $pdf = Pdf::loadView('pdf.dashboard-viagens-veiculos', [
                'veiculos' => $veiculos,
                'filtros' => $this->data,
                'periodo' => $this->getPeriodoFormatado(),
                'totalVeiculos' => count($veiculos),
                'totalViagens' => collect($veiculos)->sum('total'),
                'dataGeracao' => now()->format('d/m/Y H:i:s'),
            ]);

            $pdf->setPaper('A4', 'landscape');

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                'dashboard-viagens-veiculos-'.now()->format('Y-m-d-H-i').'.pdf'
            );
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Erro ao gerar PDF')
                ->danger()
                ->body($exception->getMessage())
                ->send();

            return null;
        }
    }

    public function getTotalViagens(): int
    {
        return collect($this->cards)->sum('total_viagens');
    }

    public function getTotalVeiculos(): int
    {
        return collect($this->cards)->pluck('veiculo_id')->filter()->unique()->count();
    }

    public function getTotalClientes(): int
    {
        return collect($this->cards)->pluck('cliente')->unique()->count();
    }

    private function getVeiculosAgrupados(): array
    {
        return collect($this->cards)
            ->groupBy('placa')
            ->map(fn ($items, $placa): array => [
                'placa' => $placa,
                'total' => $items->sum('total_viagens'),
                'clientes' => $items->sortByDesc('total_viagens')->pluck('cliente')->unique()->values()->all(),
                'viagem_atual' => $items->first()['viagem_atual'],
                'movimento_diario' => $items->first()['movimento_diario'],
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    private function getDefaultFilters(): array
    {
        return [
            'periodo' => today()->format('d/m/Y').' - '.today()->format('d/m/Y'),
            'veiculo_id' => null,
            'cliente' => null,
        ];
    }

    private function getPeriodoFiltro(): array
    {
        [$inicio, $fim] = $this->parseDateRangeFilter($this->data['periodo'] ?? null);

        return [
            $inicio?->startOfDay() ?? today()->startOfDay(),
            $fim?->endOfDay() ?? today()->endOfDay(),
        ];
    }

    private function getPeriodoFormatado(): array
    {
        [$inicio, $fim] = $this->getPeriodoFiltro();

        return [
            'inicio' => $inicio->format('d/m/Y'),
            'fim' => $fim->format('d/m/Y'),
        ];
    }

    private function getViagensAtuaisPorVeiculo(): array
    {
        return collect(app(WebScraperViagemAtualCache::class)->all())
            ->flatMap(function (array $item): array {
                $data = [
                    ...$item,
                    'destino' => filled($item['destino'] ?? null) ? (string) $item['destino'] : 'N/A',
                    'local_atual' => filled($item['local_atual'] ?? null) ? (string) $item['local_atual'] : 'N/A',
                    'peso_humano' => isset($item['peso']) ? number_format((float) $item['peso'], 2, ',', '.').' kg' : 'N/A',
                    'status' => filled($item['status'] ?? null) ? (string) $item['status'] : 'N/A',
                    'inicio_humano' => $this->formatarData($item['inicio'] ?? null),
                    'duracao_viagem' => $this->formatarDuracaoDesde($item['inicio'] ?? null),
                    'km_pago_humano' => number_format((float) ($item['km_pago'] ?? 0), 1, ',', '.'),
                ];

                $keys = [];

                if (filled($item['veiculo_key'] ?? null)) {
                    $keys[(string) $item['veiculo_key']] = $data;
                }

                if (filled($item['veiculo_id'] ?? null)) {
                    $keys['id:'.$item['veiculo_id']] = $data;
                }

                $placa = DocumentIdentity::normalizePlate($item['placa_normalizada'] ?? $item['veiculo'] ?? null);

                if ($placa !== null) {
                    $keys['placa:'.$placa] = $data;
                }

                return $keys;
            })
            ->toArray();
    }

    private function resolverViagemAtual(mixed $veiculoId, ?string $placa): array
    {
        if (filled($veiculoId) && isset($this->viagensAtuais['id:'.$veiculoId])) {
            return $this->viagensAtuais['id:'.$veiculoId];
        }

        $placaNormalizada = DocumentIdentity::normalizePlate($placa);

        if ($placaNormalizada !== null && isset($this->viagensAtuais['placa:'.$placaNormalizada])) {
            return $this->viagensAtuais['placa:'.$placaNormalizada];
        }

        return [
            'destino' => 'N/A',
            'local_atual' => 'N/A',
            'peso_humano' => 'N/A',
            'status' => 'N/A',
            'inicio_humano' => 'N/A',
            'duracao_viagem' => 'N/A',
            'km_pago_humano' => '0,0',
        ];
    }

    private function resolverMovimentoDiario(mixed $veiculoId, ?string $placa): array
    {
        $dia = $this->diaMovimento ?: today()->toDateString();
        $cache = app(SascarMovimentoDiarioCache::class);

        if (filled($veiculoId)) {
            $movimento = $cache->get('id:'.$veiculoId, $dia);

            if ($movimento !== null) {
                return $this->formatarMovimentoDiario($movimento);
            }
        }

        $placaNormalizada = DocumentIdentity::normalizePlate($placa);

        if ($placaNormalizada !== null) {
            $movimento = $cache->get('placa:'.$placaNormalizada, $dia);

            if ($movimento !== null) {
                return $this->formatarMovimentoDiario($movimento);
            }
        }

        return [
            'disponivel' => false,
            'dia' => $dia,
            'km' => null,
            'tempo_movimento' => null,
            'horas' => [],
        ];
    }

    private function formatarMovimentoDiario(array $movimento): array
    {
        $horas = collect($movimento['horas'] ?? [])
            ->sortBy('hora')
            ->map(fn (array $hora): array => [
                'hora' => (int) ($hora['hora'] ?? 0),
                'minutos' => collect($hora['minutos'] ?? [])
                    ->map(fn (mixed $status): string => match ((string) $status) {
                        '1' => '1',
                        '2' => '2',
                        default => '0',
                    })
                    ->values()
                    ->toArray(),
            ])
            ->values()
            ->toArray();

        return [
            'disponivel' => true,
            'dia' => (string) ($movimento['dia'] ?? ($this->diaMovimento ?: today()->toDateString())),
            'km' => number_format((float) ($movimento['km'] ?? 0), 1, ',', '.'),
            'tempo_movimento' => (string) ($movimento['tempo_movimento'] ?? 'N/A'),
            'horas' => $horas,
        ];
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
