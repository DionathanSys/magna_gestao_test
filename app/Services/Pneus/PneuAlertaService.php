<?php

namespace App\Services\Pneus;

use App\Models\PneuPosicaoVeiculo;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PneuAlertaService
{
    public function getFilterOptions(): array
    {
        $posicoes = $this->getPosicoesAplicadas();

        return [
            'placas' => $posicoes
                ->mapWithKeys(fn (PneuPosicaoVeiculo $posicao) => [
                    $posicao->veiculo_id => $posicao->veiculo?->placa ?? 'N/A',
                ])
                ->sortBy(fn (string $placa) => $placa)
                ->all(),
            'eixos' => $posicoes
                ->pluck('eixo', 'eixo')
                ->sortKeys()
                ->all(),
            'posicoes' => $posicoes
                ->pluck('posicao', 'posicao')
                ->sortKeys()
                ->all(),
        ];
    }

    public function getRodizioThresholdKm(): int
    {
        return (int) db_config('config-pneu.alerta_km_rodizio', 7000);
    }

    public function getDashboardData(array $filters = []): array
    {
        $posicoes = $this->getPosicoesAplicadas($filters);
        $despareamento = $this->buildDespareamentoAlerts($posicoes);
        $rodizio = $this->buildRodizioAlerts($posicoes);

        return [
            'threshold_km_rodizio' => $this->getRodizioThresholdKm(),
            'total_alertas' => $despareamento->count() + $rodizio->count(),
            'despareamento' => $despareamento,
            'rodizio' => $rodizio,
        ];
    }

    public function getPosicoesAplicadas(array $filters = []): EloquentCollection
    {
        return PneuPosicaoVeiculo::query()
            ->with([
                'pneu.cicloAtual',
                'pneu.marcaCatalogo',
                'pneu.modeloCatalogo',
                'pneu.medidaCatalogo',
                'veiculo.kmAtual',
                'mapaPosicao',
            ])
            ->aplicados()
            ->whereHas('veiculo', fn ($query) => $query->where('is_active', true))
            ->when(filled($filters['veiculo_id'] ?? null), fn ($query) => $query->where('veiculo_id', $filters['veiculo_id']))
            ->when(filled($filters['eixo'] ?? null), fn ($query) => $query->where('eixo', $filters['eixo']))
            ->when(filled($filters['posicao'] ?? null), fn ($query) => $query->where('posicao', $filters['posicao']))
            ->orderBy('veiculo_id')
            ->orderBy('eixo')
            ->orderBy('sequencia')
            ->get();
    }

    public function getKmPosicaoAtual(PneuPosicaoVeiculo $posicao): int
    {
        return (int) ($posicao->km_rodado ?? 0);
    }

    protected function buildDespareamentoAlerts(EloquentCollection $posicoes): Collection
    {
        return $posicoes
            ->groupBy(fn (PneuPosicaoVeiculo $posicao) => implode('|', [
                $posicao->veiculo_id,
                $posicao->eixo,
                $this->resolveHub($posicao),
            ]))
            ->map(function (Collection $grupo): ?array {
                if ($grupo->count() < 2) {
                    return null;
                }

                $camposDivergentes = collect([
                    'medida' => $grupo->pluck('pneu.medidaCatalogo.codigo')->filter()->unique()->values(),
                    'marca' => $grupo->pluck('pneu.marcaCatalogo.nome')->filter()->unique()->values(),
                ])->filter(fn (Collection $valores) => $valores->count() > 1);

                if ($camposDivergentes->isEmpty()) {
                    return null;
                }

                $primeiro = $grupo->first();

                return [
                    'tipo' => 'despareamento',
                    'severidade' => 'warning',
                    'placa' => $primeiro->veiculo?->placa ?? 'N/A',
                    'veiculo_id' => $primeiro->veiculo_id,
                    'eixo' => $primeiro->mapaPosicao?->eixo_numero ?? $primeiro->eixo,
                    'hub' => $this->resolveHub($primeiro),
                    'titulo' => 'Pneus despareados no '.($primeiro->mapaPosicao?->eixo_numero ?? $primeiro->eixo).'º eixo / '.$this->resolveHub($primeiro),
                    'descricao' => 'Diferenças em '.implode(', ', $camposDivergentes->keys()->all()).'.',
                    'posicoes' => $grupo->map(fn (PneuPosicaoVeiculo $posicao) => [
                        'posicao' => $posicao->mapaPosicao?->codigo ?? $posicao->posicao,
                        'posicao_nome' => $posicao->mapaPosicao?->nome,
                        'numero_fogo' => $posicao->pneu?->numero_fogo ?? 'N/A',
                        'medida' => $posicao->pneu?->medidaCatalogo?->codigo ?? '-',
                        'marca' => $posicao->pneu?->marcaCatalogo?->nome ?? '-',
                        'modelo' => $posicao->pneu?->modeloCatalogo?->nome ?? '-',
                    ])->values(),
                ];
            })
            ->filter()
            ->sortBy([
                ['placa', 'asc'],
                ['eixo', 'asc'],
                ['hub', 'asc'],
            ])
            ->values();
    }

    protected function buildRodizioAlerts(EloquentCollection $posicoes): Collection
    {
        $threshold = $this->getRodizioThresholdKm();

        return $posicoes
            ->map(function (PneuPosicaoVeiculo $posicao) use ($threshold): ?array {
                $kmPosicaoAtual = $this->getKmPosicaoAtual($posicao);

                if ($kmPosicaoAtual < $threshold) {
                    return null;
                }

                return [
                    'tipo' => 'rodizio',
                    'severidade' => 'danger',
                    'placa' => $posicao->veiculo?->placa ?? 'N/A',
                    'veiculo_id' => $posicao->veiculo_id,
                    'eixo' => $posicao->mapaPosicao?->eixo_numero ?? $posicao->eixo,
                    'hub' => $this->resolveHub($posicao),
                    'titulo' => 'Rodízio recomendado',
                    'descricao' => 'Pneu '.$posicao->pneu?->numero_fogo.' atingiu '.number_format($kmPosicaoAtual, 0, ',', '.').' km na posição atual.',
                    'posicoes' => collect([[
                        'posicao' => $posicao->mapaPosicao?->codigo ?? $posicao->posicao,
                        'posicao_nome' => $posicao->mapaPosicao?->nome,
                        'numero_fogo' => $posicao->pneu?->numero_fogo ?? 'N/A',
                        'km_posicao' => $kmPosicaoAtual,
                        'limite' => $threshold,
                    ]]),
                ];
            })
            ->filter()
            ->sortBy([
                ['placa', 'asc'],
                [fn (array $alerta) => $alerta['posicoes'][0]['km_posicao'] ?? 0, 'desc'],
            ])
            ->values();
    }

    protected function resolveHub(PneuPosicaoVeiculo $posicao): string
    {
        return match ($this->detectSide($posicao)) {
            'left' => 'LE',
            'right' => 'LD',
            default => 'CENTRO',
        };
    }

    protected function detectSide(PneuPosicaoVeiculo $posicao): ?string
    {
        $lado = strtoupper((string) $posicao->mapaPosicao?->lado);

        if ($lado === 'ESQUERDO') {
            return 'left';
        }

        if ($lado === 'DIREITO') {
            return 'right';
        }

        $texto = Str::upper(preg_replace('/[^A-Z]/', '', (string) $posicao->posicao));

        if (Str::contains($texto, ['ESQ', 'ESQUERD', 'MOTORISTA', 'LEFT'])) {
            return 'left';
        }

        if (Str::contains($texto, ['DIR', 'DIREIT', 'PASSAGEIRO', 'RIGHT'])) {
            return 'right';
        }

        if (Str::startsWith($texto, ['TEE', 'TEI', 'EE', 'EI', 'LE'])) {
            return 'left';
        }

        if (Str::startsWith($texto, ['TDE', 'TDI', 'DE', 'DI', 'LD'])) {
            return 'right';
        }

        if (Str::startsWith($texto, 'T') && isset($texto[1])) {
            return match ($texto[1]) {
                'E' => 'left',
                'D' => 'right',
                default => null,
            };
        }

        return match ($texto[0] ?? null) {
            'E' => 'left',
            'D' => 'right',
            default => null,
        };
    }
}
