<?php

namespace App\Support\Pneus;

use App\Enum\Pneu\ConfiguracaoMapaPneusEnum;
use App\Enum\Pneu\ResultadoInspecaoPneuEnum;
use App\Models\PneuPosicaoVeiculo;
use App\Models\Veiculo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MapaPneusLayout
{
    public static function build(Veiculo $veiculo, Collection $posicoes, ?int $selectedPosicaoId = null): array
    {
        $configuracao = static::resolveConfiguracao($veiculo, $posicoes);
        $eixos = $posicoes
            ->sortBy('sequencia')
            ->groupBy('eixo')
            ->sortKeys()
            ->map(fn (Collection $group, $eixo) => static::buildEixo(
                (int) $eixo,
                $group->values(),
                $selectedPosicaoId,
            ))
            ->values();

        return [
            'configuracao' => $configuracao?->value,
            'configuracao_label' => $configuracao?->label() ?? 'Mapa personalizado',
            'eixos' => $eixos->all(),
            'resumo' => [
                'total_posicoes' => $posicoes->count(),
                'total_aplicados' => $posicoes->whereNotNull('pneu_id')->count(),
                'total_inspecionados' => $posicoes->filter(fn (PneuPosicaoVeiculo $posicao) => $posicao->pneu?->inspecoes?->first())->count(),
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

    protected static function buildEixo(int $eixo, Collection $posicoes, ?int $selectedPosicaoId = null): array
    {
        $classificadas = static::classifySides($posicoes);

        return [
            'numero' => $eixo,
            'titulo' => $eixo.'º eixo',
            'left' => $classificadas['left']->map(
                fn (PneuPosicaoVeiculo $posicao, int $index) => static::formatSlot($posicao, $selectedPosicaoId, $index)
            )->all(),
            'right' => $classificadas['right']->map(
                fn (PneuPosicaoVeiculo $posicao, int $index) => static::formatSlot($posicao, $selectedPosicaoId, $index)
            )->all(),
        ];
    }

    protected static function classifySides(Collection $posicoes): array
    {
        $left = collect();
        $right = collect();
        $unknown = collect();

        foreach ($posicoes->sortBy('sequencia') as $posicao) {
            $texto = Str::lower((string) $posicao->posicao);

            if (Str::contains($texto, ['esq', 'esquerd', 'motorista', 'left'])) {
                $left->push($posicao);
                continue;
            }

            if (Str::contains($texto, ['dir', 'direit', 'passageiro', 'right'])) {
                $right->push($posicao);
                continue;
            }

            $unknown->push($posicao);
        }

        $metade = (int) ceil($unknown->count() / 2);

        return [
            'left' => $left->merge($unknown->take($metade))->values(),
            'right' => $right->merge($unknown->slice($metade))->values(),
        ];
    }

    protected static function formatSlot(PneuPosicaoVeiculo $posicao, ?int $selectedPosicaoId, int $index): array
    {
        $ultimaInspecao = $posicao->pneu?->inspecoes?->first();
        $resultado = $ultimaInspecao?->resultado;
        $status = static::status($resultado);

        return [
            'id' => $posicao->id,
            'label' => 'P'.str_pad((string) ($posicao->sequencia ?? ($index + 1)), 2, '0', STR_PAD_LEFT),
            'posicao' => $posicao->posicao,
            'sequencia' => $posicao->sequencia,
            'pneu_id' => $posicao->pneu_id,
            'numero_fogo' => $posicao->pneu?->numero_fogo,
            'marca_modelo' => trim(($posicao->pneu?->marcaCatalogo?->nome ?? '').' '.($posicao->pneu?->modeloCatalogo?->nome ?? '')),
            'resultado' => $resultado?->value,
            'status' => $status,
            'selected' => $selectedPosicaoId === $posicao->id,
            'empty' => blank($posicao->pneu_id),
            'ultima_inspecao' => $ultimaInspecao?->data_inspecao?->format('d/m/Y'),
            'km_rodado' => $posicao->km_rodado,
        ];
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
