<?php

namespace App\Services\Oficina;

use App\Models\OrdemServicoApontamento;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelatorioApontamentosMecanicosService
{
    public function obterDados(array $filtros): array
    {
        [$inicio, $fim] = $this->parsePeriodo($filtros['periodo'] ?? null);
        $colaboradorIds = array_values(array_filter($filtros['colaborador_ids'] ?? []));

        $apontamentos = OrdemServicoApontamento::query()
            ->whereHas('colaborador', fn (Builder $query): Builder => $query->where('tipo', 'MECANICO'))
            ->when($inicio && $fim, function (Builder $query) use ($inicio, $fim): Builder {
                return $query
                    ->where('iniciado_em', '<=', $fim)
                    ->where(function (Builder $query) use ($inicio): void {
                        $query
                            ->whereNull('encerrado_em')
                            ->orWhere('encerrado_em', '>=', $inicio);
                    });
            })
            ->when($colaboradorIds !== [], fn (Builder $query): Builder => $query->whereIn('colaborador_id', $colaboradorIds))
            ->with([
                'colaborador:id,codigo,nome,tipo',
                'ordemServico:id,veiculo_id,status,data_inicio,data_fim',
                'ordemServico.veiculo:id,placa',
                'itens.servico:id,codigo,descricao',
            ])
            ->orderBy('colaborador_id')
            ->orderBy('iniciado_em')
            ->orderBy('id')
            ->get();

        $grupos = $apontamentos
            ->groupBy('colaborador_id')
            ->map(fn (Collection $apontamentos): array => $this->montarGrupo($apontamentos))
            ->sortBy('colaborador_nome')
            ->values()
            ->all();

        return [
            'periodo_inicio' => $inicio?->toDateString(),
            'periodo_fim' => $fim?->toDateString(),
            'periodo_inicio_formatado' => $inicio?->format('d/m/Y'),
            'periodo_fim_formatado' => $fim?->format('d/m/Y'),
            'total_mecanicos' => count($grupos),
            'total_apontamentos' => $apontamentos->count(),
            'total_trabalhado_minutos' => collect($grupos)->sum('total_trabalhado_minutos'),
            'total_ocioso_minutos' => collect($grupos)->sum('total_ocioso_minutos'),
            'grupos' => $grupos,
        ];
    }

    private function montarGrupo(Collection $apontamentos): array
    {
        $primeiro = $apontamentos->first();
        $encerramentoAnterior = null;
        $linhas = [];
        $totalTrabalhado = 0;
        $totalOcioso = 0;

        foreach ($apontamentos->sortBy('iniciado_em')->values() as $apontamento) {
            $inicio = $apontamento->iniciado_em;
            $fim = $apontamento->encerrado_em;
            $trabalhadoMinutos = $fim ? max(0, (int) $inicio->diffInMinutes($fim, false)) : 0;
            $ociosoMinutos = null;

            if ($encerramentoAnterior && $inicio->greaterThan($encerramentoAnterior)) {
                $ociosoMinutos = (int) $encerramentoAnterior->diffInMinutes($inicio);
                $totalOcioso += $ociosoMinutos;
            }

            $totalTrabalhado += $trabalhadoMinutos;

            $linhas[] = [
                'id' => $apontamento->id,
                'ordem_servico_id' => $apontamento->ordem_servico_id,
                'veiculo' => $apontamento->ordemServico?->veiculo?->placa ?? '-',
                'iniciado_em' => $inicio?->toDateTimeString(),
                'encerrado_em' => $fim?->toDateTimeString(),
                'iniciado_em_formatado' => $inicio?->format('d/m/Y H:i') ?? '-',
                'encerrado_em_formatado' => $fim?->format('d/m/Y H:i') ?? 'Aberto',
                'trabalhado_minutos' => $trabalhadoMinutos,
                'ocioso_minutos' => $ociosoMinutos,
                'servicos' => $apontamento->itens
                    ->map(fn ($item): string => trim(($item->servico?->codigo ? $item->servico->codigo.' - ' : '').($item->servico?->descricao ?? 'Serviço não informado')))
                    ->filter()
                    ->values()
                    ->all(),
            ];

            if ($fim && (! $encerramentoAnterior || $fim->greaterThan($encerramentoAnterior))) {
                $encerramentoAnterior = $fim;
            }
        }

        return [
            'colaborador_id' => $primeiro->colaborador_id,
            'colaborador_codigo' => $primeiro->colaborador?->codigo ?? '-',
            'colaborador_nome' => $primeiro->colaborador?->nome ?? 'Mecânico não informado',
            'total_apontamentos' => count($linhas),
            'total_trabalhado_minutos' => $totalTrabalhado,
            'total_ocioso_minutos' => $totalOcioso,
            'linhas' => $linhas,
        ];
    }

    public function parsePeriodo(mixed $value): array
    {
        try {
            if (is_array($value)) {
                $values = array_values($value);

                return [
                    $this->parseDateValue($value['startDate'] ?? $value['start'] ?? $values[0] ?? null)?->startOfDay(),
                    $this->parseDateValue($value['endDate'] ?? $value['end'] ?? $values[1] ?? null)?->endOfDay(),
                ];
            }

            if (is_string($value) && str_contains($value, ' - ')) {
                [$rawInicio, $rawFim] = array_map('trim', explode(' - ', $value, 2));

                return [
                    $this->parseDateValue($rawInicio)?->startOfDay(),
                    $this->parseDateValue($rawFim)?->endOfDay(),
                ];
            }

            if (is_string($value) && filled($value)) {
                return [
                    $this->parseDateValue($value)?->startOfDay(),
                    $this->parseDateValue($value)?->endOfDay(),
                ];
            }
        } catch (\Throwable) {
            return [null, null];
        }

        return [null, null];
    }

    private function parseDateValue(mixed $value): ?Carbon
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value) === 1) {
            return Carbon::createFromFormat('d/m/Y', $value);
        }

        $isoDate = Str::of($value)->before('T');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $isoDate->value()) === 1) {
            return Carbon::createFromFormat('Y-m-d', $isoDate->value());
        }

        return Carbon::parse($value);
    }
}
