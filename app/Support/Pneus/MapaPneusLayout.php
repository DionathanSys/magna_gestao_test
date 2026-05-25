<?php

namespace App\Support\Pneus;

use App\Enum\Pneu\ConfiguracaoMapaPneusEnum;
use App\Enum\Pneu\ResultadoInspecaoPneuEnum;
use App\Models\HistoricoMovimentoPneu;
use App\Models\PneuPosicaoVeiculo;
use App\Models\Veiculo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MapaPneusLayout
{
    public static function build(Veiculo $veiculo, Collection $posicoes, ?int $selectedPosicaoId = null): array
    {
        $configuracao = static::resolveConfiguracao($veiculo, $posicoes);
        $kmHistoricoPorCiclo = static::resolveKmHistoricoPorCiclo($posicoes);
        $eixos = $posicoes
            ->sortBy('sequencia')
            ->groupBy('eixo')
            ->sortKeys()
            ->map(fn (Collection $group, $eixo) => static::buildEixo(
                (int) $eixo,
                $group->values(),
                $selectedPosicaoId,
                $kmHistoricoPorCiclo,
            ))
            ->values();

        return [
            'configuracao' => $configuracao?->value,
            'configuracao_label' => $configuracao?->label() ?? 'Mapa personalizado',
            'eixos' => $eixos->all(),
            'resumo' => [
                'total_posicoes' => $posicoes->count(),
                'total_aplicados' => $posicoes->whereNotNull('pneu_id')->count(),
                'total_inspecionados' => $posicoes->filter(fn (PneuPosicaoVeiculo $posicao) => $posicao->pneu?->ultimaInspecao)->count(),
            ],
        ];
    }

    public static function resolveConfiguracao(Veiculo $veiculo, Collection $posicoes): ?ConfiguracaoMapaPneusEnum
    {
        $configuracao = $veiculo->tipoVeiculo?->configuracao_pneus;

        if ($configuracao instanceof ConfiguracaoMapaPneusEnum) {
            return $configuracao;
        }

        if (is_string($configuracao) && ConfiguracaoMapaPneusEnum::tryFrom($configuracao)) {
            return ConfiguracaoMapaPneusEnum::from($configuracao);
        }

        $descricao = Str::lower((string) $veiculo->tipoVeiculo?->descricao);

        if (Str::contains($descricao, '8x2')) {
            return ConfiguracaoMapaPneusEnum::CAMINHAO_8X2;
        }

        if (Str::contains($descricao, '6x2')) {
            return ConfiguracaoMapaPneusEnum::CAMINHAO_6X2;
        }

        return $posicoes->max('eixo') >= 4
            ? ConfiguracaoMapaPneusEnum::CAMINHAO_8X2
            : ConfiguracaoMapaPneusEnum::CAMINHAO_6X2;
    }

    protected static function buildEixo(int $eixo, Collection $posicoes, ?int $selectedPosicaoId = null, array $kmHistoricoPorCiclo = []): array
    {
        $classificadas = static::classifySides($posicoes);

        return [
            'numero' => $eixo,
            'titulo' => $eixo.'º eixo',
            'left' => $classificadas['left']->map(
                fn (PneuPosicaoVeiculo $posicao, int $index) => static::formatSlot($posicao, $selectedPosicaoId, $index, $kmHistoricoPorCiclo)
            )->all(),
            'right' => $classificadas['right']->map(
                fn (PneuPosicaoVeiculo $posicao, int $index) => static::formatSlot($posicao, $selectedPosicaoId, $index, $kmHistoricoPorCiclo)
            )->all(),
        ];
    }

    protected static function classifySides(Collection $posicoes): array
    {
        $left = collect();
        $right = collect();
        $unknown = collect();

        foreach ($posicoes->sortBy('sequencia') as $posicao) {
            $side = static::detectSide($posicao);

            if ($side === 'left') {
                $left->push($posicao);
                continue;
            }

            if ($side === 'right') {
                $right->push($posicao);
                continue;
            }

            $unknown->push($posicao);
        }

        $metade = (int) ceil($unknown->count() / 2);

        return [
            'left' => static::sortSidePositions($left->merge($unknown->take($metade))->values(), 'left'),
            'right' => static::sortSidePositions($right->merge($unknown->slice($metade))->values(), 'right'),
        ];
    }

    protected static function detectSide(PneuPosicaoVeiculo $posicao): ?string
    {
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

    protected static function sortSidePositions(Collection $posicoes, string $side): Collection
    {
        return $posicoes
            ->sort(function (PneuPosicaoVeiculo $a, PneuPosicaoVeiculo $b) use ($side): int {
                $sequenciaA = (int) ($a->sequencia ?? 0);
                $sequenciaB = (int) ($b->sequencia ?? 0);

                if ($sequenciaA !== $sequenciaB) {
                    return $sequenciaB <=> $sequenciaA;
                }

                return static::getSideOrderWeight($a, $side) <=> static::getSideOrderWeight($b, $side);
            })
            ->values();
    }

    protected static function getSideOrderWeight(PneuPosicaoVeiculo $posicao, string $side): int
    {
        $texto = Str::upper(preg_replace('/[^A-Z]/', '', (string) $posicao->posicao));
        $isInterno = Str::contains($texto, 'I') || Str::contains($texto, 'INT');
        $isExterno = Str::contains($texto, 'E') || Str::contains($texto, 'EXT');

        if ($side === 'left') {
            return $isExterno ? 0 : ($isInterno ? 1 : 2);
        }

        return $isInterno ? 0 : ($isExterno ? 1 : 2);
    }

    protected static function formatSlot(PneuPosicaoVeiculo $posicao, ?int $selectedPosicaoId, int $index, array $kmHistoricoPorCiclo = []): array
    {
        $ultimaInspecao = $posicao->pneu?->ultimaInspecao;
        $resultado = $ultimaInspecao?->resultado;
        $status = static::status($resultado);
        $kmHistorico = 0;
        $temKmAplicado = false;
        $modelo = $posicao->pneu?->modeloCatalogo?->nome;
        $ultimoRecapDoCicloAtual = $posicao->pneu?->ultimoRecap;
        $desenhoAtual = ((int) $ultimoRecapDoCicloAtual?->ciclo_vida === (int) $posicao->pneu?->ciclo_vida)
            ? $ultimoRecapDoCicloAtual?->desenhoPneu?->descricao
            : null;

        $desenhoAtual ??= $posicao->pneu?->cicloAtual?->desenhoPneu?->descricao
            ?? $posicao->pneu?->desenhoPneu?->descricao;

        if ($posicao->pneu) {
            $kmHistorico = $kmHistoricoPorCiclo[static::makeHistoricoKey($posicao->pneu->id, (int) $posicao->pneu->ciclo_vida)] ?? 0;

            $temKmAplicado = $kmHistorico > 0 || filled($posicao->km_rodado);
        }

        $kmCicloAtual = $temKmAplicado
            ? (float) ($kmHistorico + ($posicao->km_rodado ?? 0))
            : null;

        return [
            'id' => $posicao->id,
            'label' => 'P'.str_pad((string) ($posicao->sequencia ?? ($index + 1)), 2, '0', STR_PAD_LEFT),
            'posicao' => $posicao->posicao,
            'sequencia' => $posicao->sequencia,
            'pneu_id' => $posicao->pneu_id,
            'numero_fogo' => $posicao->pneu?->numero_fogo,
            'marca_modelo' => trim(($posicao->pneu?->marcaCatalogo?->nome ?? '').' '.($posicao->pneu?->modeloCatalogo?->nome ?? '')),
            'modelo' => $modelo,
            'desenho_atual' => $desenhoAtual,
            'resultado' => $resultado?->value,
            'status' => $status,
            'selected' => $selectedPosicaoId === $posicao->id,
            'empty' => blank($posicao->pneu_id),
            'ultima_inspecao' => $ultimaInspecao?->data_inspecao?->format('d/m/Y'),
            'km_rodado' => $posicao->km_rodado,
            'km_ciclo_atual' => $kmCicloAtual,
        ];
    }

    protected static function resolveKmHistoricoPorCiclo(Collection $posicoes): array
    {
        $pairs = $posicoes
            ->filter(fn (PneuPosicaoVeiculo $posicao) => filled($posicao->pneu_id))
            ->map(fn (PneuPosicaoVeiculo $posicao) => [
                'pneu_id' => (int) $posicao->pneu_id,
                'ciclo_vida' => (int) ($posicao->pneu?->ciclo_vida ?? 0),
            ])
            ->unique(fn (array $item) => static::makeHistoricoKey($item['pneu_id'], $item['ciclo_vida']))
            ->values();

        if ($pairs->isEmpty()) {
            return [];
        }

        return HistoricoMovimentoPneu::query()
            ->selectRaw('pneu_id, ciclo_vida, SUM(km_percorrido) as total_km')
            ->where(function ($query) use ($pairs): void {
                foreach ($pairs as $pair) {
                    $query->orWhere(function ($subQuery) use ($pair): void {
                        $subQuery
                            ->where('pneu_id', $pair['pneu_id'])
                            ->where('ciclo_vida', $pair['ciclo_vida']);
                    });
                }
            })
            ->groupBy('pneu_id', 'ciclo_vida')
            ->get()
            ->mapWithKeys(fn ($item) => [
                static::makeHistoricoKey((int) $item->pneu_id, (int) $item->ciclo_vida) => (float) $item->total_km,
            ])
            ->all();
    }

    protected static function makeHistoricoKey(int $pneuId, int $cicloVida): string
    {
        return $pneuId.'|'.$cicloVida;
    }

    protected static function status(?ResultadoInspecaoPneuEnum $resultado): string
    {
        return match ($resultado) {
            ResultadoInspecaoPneuEnum::APROVADO => 'ok',
            ResultadoInspecaoPneuEnum::MONITORAR,
            ResultadoInspecaoPneuEnum::AGUARDANDO_CONSERTO => 'warning',
            ResultadoInspecaoPneuEnum::APTO_RECAPAGEM => 'info',
            ResultadoInspecaoPneuEnum::REPROVADO,
            ResultadoInspecaoPneuEnum::CONDENADO => 'danger',
            default => 'neutral',
        };
    }
}
