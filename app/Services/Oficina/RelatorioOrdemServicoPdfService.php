<?php

namespace App\Services\Oficina;

use App\Models\OrdemServico;
use Barryvdh\DomPDF\Facade\Pdf;

class RelatorioOrdemServicoPdfService
{
    public function visualizar(OrdemServico $ordemServico)
    {
        abort_unless($ordemServico->parceiro_id === null, 404);

        $ordemServico->load([
            'veiculo',
            'itens.servico',
            'apontamentosOficina.colaborador',
            'apontamentosOficina.itens.servico',
        ]);

        return Pdf::loadView('pdf.oficina-ordem-servico', [
            'ordemServico' => $ordemServico,
            'dataGeracao' => now()->format('d/m/Y H:i'),
        ])
            ->setPaper('A4', 'portrait')
            ->stream('relatorio-oficina-os-'.$ordemServico->id.'.pdf');
    }
}
