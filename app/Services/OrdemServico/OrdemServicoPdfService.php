<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServico;
use App\Services\Pdf\MakePdf;
use Barryvdh\DomPDF\Facade\Pdf;

class OrdemServicoPdfService
{
    /**
     * Gera o PDF da Ordem de Serviço para download
     */
    public function gerarPdfOrdemServico(OrdemServico $ordemServico)
    {
        // Carregar relacionamentos necessários
        $ordemServico->load([
            'veiculo.kmAtual',
            'planoPreventivoVinculado.planoPreventivo',
            'parceiro',
            'sankhyaId',
            'itens.servico',
            'itens.creator',
            'creator'
        ]);

        $data = [
            'ordemServico' => $ordemServico,
            'dataGeracao' => now()->format('d/m/Y H:i:s')
        ];

        $pdf = Pdf::loadView('pdf.ordem-servico', $data);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->stream();
            }, 'ordem-servico-' . date('Y-m-d-H-i') . '.pdf');

    }

    /**
     * Visualiza o PDF da Ordem de Serviço no navegador
     */
    public function visualizarPdfOrdemServico(OrdemServico $ordemServico)
    {
        // Carregar relacionamentos necessários
        $ordemServico->load([
            'veiculo.kmAtual',
            'planoPreventivoVinculado.planoPreventivo',
            'parceiro',
            'sankhyaId',
            'itens.servico',
            'itens.creator',
            'creator'
        ]);

        $data = [
            'ordemServico' => $ordemServico,
            'dataGeracao' => now()->format('d/m/Y H:i:s')
        ];

        // Para visualizar no navegador
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ordem-servico', $data)
            ->setPaper('A4', 'portrait')
            ->stream('ordem-servico-' . $ordemServico->id . '-' . date('Y-m-d-H-i') . '.pdf');
    }

    /**
     * Gera o PDF da Ordem de Serviço com Tailwind CSS para download
     */
    public function gerarPdfOrdemServicoTailwind(OrdemServico $ordemServico)
    {
        // Carregar relacionamentos necessários
        $ordemServico->load([
            'veiculo.kmAtual',
            'planoPreventivoVinculado.planoPreventivo',
            'parceiro',
            'sankhyaId',
            'itens.servico',
            'itens.creator',
            'creator'
        ]);

        $data = [
            'ordemServico' => $ordemServico,
            'dataGeracao' => now()->format('d/m/Y H:i:s')
        ];

        $pdf = Pdf::loadView('pdf.ordem-servico', $data);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->stream();
            }, 'ordem-servico-tailwind-' . date('Y-m-d-H-i') . '.pdf');
    }

    /**
     * Visualiza o PDF da Ordem de Serviço com Tailwind CSS no navegador
     */
    public function visualizarPdfOrdemServicoTailwind(OrdemServico $ordemServico)
    {
        // Carregar relacionamentos necessários
        $ordemServico->load([
            'veiculo.kmAtual',
            'planoPreventivoVinculado.planoPreventivo',
            'parceiro',
            'sankhyaId',
            'itens.servico',
            'itens.creator',
            'creator'
        ]);

        $data = [
            'ordemServico' => $ordemServico,
            'dataGeracao' => now()->format('d/m/Y H:i:s')
        ];

        // Para visualizar no navegador
        return view('pdf.ordem-servico', $data);
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
