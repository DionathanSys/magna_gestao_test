<?php

namespace App\Services\PlanoManutencao;

use \App\Models;
use App\Services\Pdf\MakePdf;
use Barryvdh\DomPDF\Facade\Pdf;

class PlanoManutencaoService
{
    public function obterVencimentoPlanosPreventivos(int $kmTolerancia = 2500): array
    {
        $previsaoPlanos = [];

        $idPlanosPreventivos = Models\PlanoPreventivo::query()
            ->select('id')
            ->where('is_active', true)
            ->get()
            ->pluck('id')
            ->toArray();

        $planoVeiculos = Models\PlanoManutencaoVeiculo::query()
            ->select('veiculo_id', 'plano_preventivo_id')
            ->whereIn('plano_preventivo_id', $idPlanosPreventivos)
            ->get();

        $planoVeiculos->each(function ($planoVeiculo) use (&$previsaoPlanos, $kmTolerancia) {
            $previsaoPlano = (new CalcularPrevisaoPlano($planoVeiculo))
                ->exec();

            if ($previsaoPlano['km_restante'] <= $kmTolerancia) {
                $previsaoPlano['placa'] = $planoVeiculo->veiculo->placa ?? 'N/A';
                $previsaoPlanos[] = $previsaoPlano;
            }

        });

        return $previsaoPlanos;
    }

    public function gerarRelatorioVencimentoPdf(int $kmTolerancia = 2500)
    {
        $planos = $this->obterVencimentoPlanosPreventivos($kmTolerancia);

        $data = [
            'planos' => $planos,
            'kmTolerancia' => $kmTolerancia,
            'totalPlanos' => count($planos),
            'dataGeracao' => now()->format('d/m/Y H:i:s')
        ];

        $pdf = Pdf::loadView('pdf.planos-vencimento', $data);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->stream();
            }, 'relatorio-planos-vencimento-' . date('Y-m-d-H-i') . '.pdf');


    }

    public function visualizarRelatorioVencimentoPdf(int $kmTolerancia = 2500)
    {
        $planos = $this->obterVencimentoPlanosPreventivos($kmTolerancia);

        // Garantir que todos os dados estejam em UTF-8
        $planos = $this->sanitizeUtf8Data($planos);

        $data = [
            'planos' => $planos,
            'kmTolerancia' => $kmTolerancia,
            'totalPlanos' => count($planos),
            'dataGeracao' => now()->format('d/m/Y H:i:s')
        ];

        // Para visualizar no navegador, usamos diretamente o DomPDF
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.planos-vencimento', $data)
            ->setPaper('A4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'isFontSubsettingEnabled' => false,
                'isRemoteEnabled' => true,
                'chroot' => base_path(),
            ])
            ->stream('relatorio-planos-vencimento-' . date('Y-m-d-H-i') . '.pdf');
    }

    /**
     * Sanitiza dados para UTF-8 correto, evitando caracteres malformados
     */
    private function sanitizeUtf8Data(array $data): array
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                return $this->sanitizeUtf8Data($item);
            } elseif (is_string($item)) {
                // Garantir codificação UTF-8 correta
                if (!mb_check_encoding($item, 'UTF-8')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'auto');
                }

                // Remove caracteres de controle que podem causar problemas no PDF
                $item = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $item);

                // Remove sequências UTF-8 inválidas
                $item = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $item);

                return trim($item);
            }
            return $item;
        }, $data);
    }

}
