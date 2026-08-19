<?php

namespace App\Filament\Pages;

use App\Enum\ClienteEnum;
use App\Enum\MotivoDivergenciaViagem;
use App\Models\JustificativaDispersaoViagem;
use App\Models\Veiculo;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AnaliseJustificativasDispersao extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected string $view = 'filament.pages.analise-justificativas-dispersao';

    protected static ?string $navigationLabel = 'Análise de Justificativas';

    protected static ?string $title = 'Análise de Justificativas de Dispersão';

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    public ?array $data = [];

    public array $indicadores = [];

    public array $resumoMotivos = [];

    public array $justificativas = [];

    public function mount(): void
    {
        $this->data = $this->getDefaultData();
        $this->carregarDados();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Filtros')
                    ->description('Os filtros consideram os dados salvos no momento da justificativa.')
                    ->columns(5)
                    ->columnSpan(12)
                    ->components([
                        DatePicker::make('data_inicio')
                            ->label('Data competência inicial')
                            ->native(false),
                        DatePicker::make('data_fim')
                            ->label('Data competência final')
                            ->native(false),
                        Select::make('veiculo_id')
                            ->label('Placa')
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
                        Select::make('motivo')
                            ->label('Motivo')
                            ->placeholder('Todos')
                            ->options(MotivoDivergenciaViagem::toSelectArray())
                            ->searchable()
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function carregarDados(): void
    {
        $query = $this->queryFiltrada();

        $this->indicadores = (clone $query)
            ->selectRaw('COUNT(*) as total_justificativas')
            ->selectRaw('COUNT(DISTINCT justificativas_dispersao_viagens.viagem_id) as total_viagens')
            ->selectRaw('COALESCE(SUM(justificativas_dispersao_viagens.km_dispersao), 0) as total_km_dispersao')
            ->selectRaw('COALESCE(AVG(justificativas_dispersao_viagens.dispersao_percentual), 0) as media_percentual_dispersao')
            ->first()
            ?->toArray() ?? [];

        $totalKmDispersao = (float) ($this->indicadores['total_km_dispersao'] ?? 0);

        $this->resumoMotivos = (clone $query)
            ->select('justificativas_dispersao_viagens.motivo')
            ->selectRaw('COUNT(*) as total_justificativas')
            ->selectRaw('COUNT(DISTINCT justificativas_dispersao_viagens.viagem_id) as total_viagens')
            ->selectRaw('COALESCE(SUM(justificativas_dispersao_viagens.km_dispersao), 0) as total_km_dispersao')
            ->selectRaw('COALESCE(AVG(justificativas_dispersao_viagens.dispersao_percentual), 0) as media_percentual_dispersao')
            ->groupBy('justificativas_dispersao_viagens.motivo')
            ->orderByDesc('total_km_dispersao')
            ->get()
            ->map(fn ($item): array => [
                ...$item->toArray(),
                'participacao_percentual' => $totalKmDispersao > 0
                    ? ((float) $item->total_km_dispersao / $totalKmDispersao) * 100
                    : 0,
            ])
            ->all();

        $this->justificativas = (clone $query)
            ->select([
                'justificativas_dispersao_viagens.id',
                'justificativas_dispersao_viagens.viagem_id',
                'justificativas_dispersao_viagens.motivo',
                'justificativas_dispersao_viagens.observacao',
                'justificativas_dispersao_viagens.numero_viagem',
                'justificativas_dispersao_viagens.veiculo_placa',
                'justificativas_dispersao_viagens.data_competencia',
                'justificativas_dispersao_viagens.km_dispersao',
                'justificativas_dispersao_viagens.dispersao_percentual',
                'justificativas_dispersao_viagens.created_at',
                'users.name as criador_nome',
            ])
            ->leftJoin('users', 'users.id', '=', 'justificativas_dispersao_viagens.created_by')
            ->orderByDesc('justificativas_dispersao_viagens.created_at')
            ->limit(200)
            ->get()
            ->map(fn ($item): array => $item->toArray())
            ->all();
    }

    public function limparFiltros(): void
    {
        $this->data = $this->getDefaultData();
        $this->carregarDados();
    }

    public function selecionarMotivo(string $motivo): void
    {
        $this->data['motivo'] = $motivo;
        $this->carregarDados();
    }

    private function getDefaultData(): array
    {
        return [
            'data_inicio' => now()->startOfMonth()->toDateString(),
            'data_fim' => now()->endOfMonth()->toDateString(),
            'veiculo_id' => null,
            'cliente' => null,
            'motivo' => null,
        ];
    }

    private function queryFiltrada(): Builder
    {
        return JustificativaDispersaoViagem::query()
            ->join('viagens', 'viagens.id', '=', 'justificativas_dispersao_viagens.viagem_id')
            ->leftJoin('veiculos', 'veiculos.id', '=', 'viagens.veiculo_id')
            ->when(
                filled($this->data['data_inicio'] ?? null),
                fn (Builder $query): Builder => $query->whereDate('justificativas_dispersao_viagens.data_competencia', '>=', $this->data['data_inicio']),
            )
            ->when(
                filled($this->data['data_fim'] ?? null),
                fn (Builder $query): Builder => $query->whereDate('justificativas_dispersao_viagens.data_competencia', '<=', $this->data['data_fim']),
            )
            ->when(
                filled($this->data['veiculo_id'] ?? null),
                fn (Builder $query): Builder => $query->where('viagens.veiculo_id', $this->data['veiculo_id']),
            )
            ->when(
                filled($this->data['cliente'] ?? null),
                fn (Builder $query): Builder => $query->where('veiculos.informacoes_complementares->cliente', $this->data['cliente']),
            )
            ->when(
                filled($this->data['motivo'] ?? null),
                fn (Builder $query): Builder => $query->where('justificativas_dispersao_viagens.motivo', $this->data['motivo']),
            );
    }
}
