<?php

namespace App\Filament\Pages;

use App\Enum\ClienteEnum;
use App\Models\Veiculo;
use App\Models\Viagem;
use App\Services\MailInbound\Support\DocumentIdentity;
use App\Services\WebScraper\WebScraperViagemAtualCache;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class DashboardViagensVeiculos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected string $view = 'filament.pages.dashboard-viagens-veiculos';

    protected static ?string $navigationLabel = 'Viagens por Veículo';

    protected static ?string $title = 'Dashboard de Viagens por Veículo';

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    public ?array $data = [];

    public array $cards = [];

    private array $viagensAtuais = [];

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
                    ->description('Os dados são buscados diretamente da tabela viagens.')
                    ->columns(4)
                    ->columnSpan(12)
                    ->components([
                        DatePicker::make('data_inicio')
                            ->label('Data inicial')
                            ->native(false)
                            ->required(),

                        DatePicker::make('data_fim')
                            ->label('Data final')
                            ->native(false)
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
                            ->searchable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function carregarDados(): void
    {
        $dataInicio = Carbon::parse($this->data['data_inicio'] ?? today())->toDateString();
        $dataFim = Carbon::parse($this->data['data_fim'] ?? today())->toDateString();
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
                'viagens.cliente',
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
                fn ($query) => $query->where('viagens.cliente', $this->data['cliente']),
            )
            ->groupBy('viagens.veiculo_id', 'veiculos.placa', 'viagens.cliente')
            ->orderByDesc('total_viagens')
            ->orderBy('placa')
            ->get()
            ->map(fn ($item): array => [
                'veiculo_id' => $item->veiculo_id,
                'placa' => $item->placa,
                'cliente' => $item->cliente ?: 'Sem cliente',
                'total_viagens' => (int) $item->total_viagens,
                'viagem_atual' => $this->resolverViagemAtual($item->veiculo_id, $item->placa),
            ])
            ->toArray();
    }

    public function limparFiltros(): void
    {
        $this->data = $this->getDefaultFilters();
        $this->carregarDados();
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

    private function getDefaultFilters(): array
    {
        return [
            'data_inicio' => today()->toDateString(),
            'data_fim' => today()->toDateString(),
            'veiculo_id' => null,
            'cliente' => null,
        ];
    }

    private function getViagensAtuaisPorVeiculo(): array
    {
        return collect(app(WebScraperViagemAtualCache::class)->all())
            ->flatMap(function (array $item): array {
                $data = [
                    ...$item,
                    'destino' => filled($item['destino'] ?? null) ? (string) $item['destino'] : 'N/A',
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
            'status' => 'N/A',
            'inicio_humano' => 'N/A',
            'duracao_viagem' => 'N/A',
            'km_pago_humano' => '0,0',
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
