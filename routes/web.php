<?php

use App\Imports\DocumentoFreteImport;
use App\Models\Pneu;
use App\Services\DocumentoFrete\DocumentoFreteService;
use Illuminate\Support\Facades\Route;

Route::get('/teste', function () {
    $file = "01K3GQ73RG5NV1D35A0A4PJ7G2.xls";
    $importer = new DocumentoFreteImport();
    $service = new DocumentoFreteService();
    $service->importarRelatorioDocumentoFrete($importer, $file);
});

Route::get('/ordem-servico/{ordemServico}/pdf', function (\App\Models\OrdemServico $ordemServico) {
    $service = new \App\Services\OrdemServico\OrdemServicoPdfService();
    return $service->visualizarPdfOrdemServico($ordemServico);
})->name('ordem-servico.pdf.visualizar');

Route::get('/teste', function () {
echo now()->format('Y-m-d H:i:s T'); // Mostra data/hora com timezone
echo config('app.timezone'); // Mostra o timezone configurado
});
