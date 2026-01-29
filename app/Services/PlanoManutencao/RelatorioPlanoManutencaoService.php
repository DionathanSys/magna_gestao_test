<?php

namespace App\Services\PlanoManutencao;

use App\Models\PlanoManutencaoVeiculo;
use App\Models\PlanoPreventivo;
use App\Models\Veiculo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RelatorioPlanoManutencaoService
{
    /**
     * Busca os dados do relatório com base nos filtros
     *
     * @param array $filtros
     * @return array
     */
    public function obterDadosRelatorio(array $filtros = []): array
    {
        $query = PlanoManutencaoVeiculo::query()
            ->with([
                'veiculo.kmAtual',
                'planoPreventivo'
            ]);

        // Filtrar por veículo se especificado, caso contrário apenas ativos
        if (!empty($filtros['veiculo_id'])) {
            $query->where('veiculo_id', $filtros['veiculo_id']);
        } else {
            $query->whereHas('veiculo', function ($q) {
                $q->where('is_active', true);
            });
        }

        // Filtrar por plano preventivo se especificado, caso contrário apenas ativos
        if (!empty($filtros['plano_preventivo_id'])) {
            $query->where('plano_preventivo_id', $filtros['plano_preventivo_id']);
        } else {
            $query->whereHas('planoPreventivo', function ($q) {
                $q->where('is_active', true);
            });
        }

        $planosVeiculos = $query->get();

        Log::info('Total de planos de manutenção veicular encontrados: ' . $planosVeiculos->count());

        $dados = [];

        foreach ($planosVeiculos as $planoVeiculo) {
            $veiculo = $planoVeiculo->veiculo;
            $planoPreventivo = $planoVeiculo->planoPreventivo;
            $ultimaExecucao = $planoVeiculo->ultima_execucao;

            Log::info("Verificando última execução", [
                'veiculo_id' => $planoVeiculo->veiculo_id,
                'plano_preventivo_id' => $planoVeiculo->plano_preventivo_id,
                'ultima_execucao_encontrada' => $ultimaExecucao ? 'Sim' : 'Não',
                'km_execucao' => $ultimaExecucao?->km_execucao ?? 'N/A'
            ]);

            if (!$veiculo || !$planoPreventivo) {
                continue;
            }

            $kmAtual = $veiculo->quilometragem_atual;
            $kmUltimaExecucao = $ultimaExecucao?->km_execucao ?? 0;
            $dataUltimaExecucao = $ultimaExecucao?->created_at;
            
            $proximaExecucao = $kmUltimaExecucao + $planoPreventivo->intervalo;
            $kmRestante = $proximaExecucao - $kmAtual;

            Log::info("Veículo ID {$veiculo->id} - KM Restante: {$kmRestante}", [
                'plano_preventivo_id' => $planoPreventivo->descricao,
                'km_atual' => $kmAtual,
                'km_ultima_execucao' => $kmUltimaExecucao,
                'proxima_execucao' => $proximaExecucao,
            ]);

            // Filtrar por km_restante_maximo se especificado
            if (isset($filtros['km_restante_maximo']) && $filtros['km_restante_maximo'] !== null) {
                if ($kmRestante > $filtros['km_restante_maximo']) {
                    continue;
                }
            }

            // Calcular km médio e data prevista
            $kmMedioDiario = $veiculo->calcularKmMedioDiario(30);
            
            // Se km_restante for negativo, marcar como "Atrasado"
            if ($kmRestante < 0) {
                $dataPrevista = 'Atrasado';
            } else {
                $dataPrevistaObj = $veiculo->calcularDataPrevista($kmRestante);
                $dataPrevista = $dataPrevistaObj ? $dataPrevistaObj->format('Y-m-d') : null;
            }

            $dados[] = [
                'veiculo_id' => (int) $veiculo->id,
                'placa' => (string) $veiculo->placa,
                'plano_descricao' => (string) $planoPreventivo->descricao,
                'periodicidade' => (float) $planoPreventivo->intervalo,
                'km_atual' => (float) $kmAtual,
                'km_ultima_execucao' => (float) $kmUltimaExecucao,
                'data_ultima_execucao' => $dataUltimaExecucao ? $dataUltimaExecucao->format('Y-m-d') : null,
                'proxima_execucao' => (float) $proximaExecucao,
                'km_restante' => (float) $kmRestante,
                'km_medio_diario' => (float) $kmMedioDiario,
                'data_prevista' => $dataPrevista,
            ];
        }

        Log::info('Total de registros após aplicação dos filtros: ' . count($dados));

        // Ordenar por placa do veículo e depois por km_restante (menor primeiro)
        usort($dados, function ($a, $b) {
            $placaCompare = strcmp($a['placa'], $b['placa']);
            if ($placaCompare !== 0) {
                return $placaCompare;
            }
            Log::debug("Comparando KM Restante: {$a['km_restante']} com {$b['km_restante']}");
            return $a['km_restante'] <=> $b['km_restante'];
        });

        return $dados;
    }

    /**
     * Gera o relatório em PDF e faz download
     *
     * @param array $filtros
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function gerarRelatorio(array $filtros = [])
    {
        $dados = $this->obterDadosRelatorio($filtros);

        // Sanitizar dados
        $dados = $this->sanitizeUtf8Data($dados);

        $data = [
            'dados' => $dados,
            'filtros' => $filtros,
            'totalRegistros' => count($dados),
            'dataGeracao' => now()->format('d/m/Y H:i:s')
        ];

        $pdf = Pdf::loadView('pdf.relatorio-plano-manutencao', $data);
        $pdf->setPaper('A4', 'landscape');

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->stream();
            },
            'relatorio-plano-manutencao-' . date('Y-m-d-H-i') . '.pdf'
        );
    }

    /**
     * Visualiza o relatório em PDF no navegador
     *
     * @param array $filtros
     * @return \Illuminate\Http\Response
     */
    public function visualizarRelatorio(array $filtros = []): \Illuminate\Http\Response
    {
        $dados = $this->obterDadosRelatorio($filtros);

        // Sanitizar dados
        $dados = $this->sanitizeUtf8Data($dados);

        $data = [
            'dados' => $dados,
            'filtros' => $filtros,
            'totalRegistros' => count($dados),
            'dataGeracao' => now()->format('d/m/Y H:i:s')
        ];

        return Pdf::loadView('pdf.relatorio-plano-manutencao', $data)
            ->setPaper('A4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'isFontSubsettingEnabled' => false,
                'isRemoteEnabled' => true,
                'chroot' => base_path(),
            ])
            ->stream('relatorio-plano-manutencao-' . date('Y-m-d-H-i') . '.pdf');
    }

    /**
     * Sanitiza dados para UTF-8 correto
     *
     * @param array $data
     * @return array
     */
    private function sanitizeUtf8Data(array $data): array
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                return $this->sanitizeUtf8Data($item);
            } elseif ($item instanceof \Carbon\Carbon) {
                // Converter objetos Carbon para string
                return $item->format('Y-m-d H:i:s');
            } elseif (is_object($item)) {
                // Converter outros objetos para string ou null
                try {
                    return (string) $item;
                } catch (\Exception $e) {
                    return null;
                }
            } elseif (is_string($item)) {
                // Garantir codificação UTF-8 correta
                if (!mb_check_encoding($item, 'UTF-8')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
                // Remover caracteres inválidos
                return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $item);
            } elseif (is_numeric($item)) {
                return $item;
            } elseif (is_bool($item)) {
                return $item;
            } elseif (is_null($item)) {
                return null;
            }
            return $item;
        }, $data);
    }
}
