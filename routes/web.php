<?php

use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessImportRowJob;
use App\Jobs\TesteJob;
use App\Models\Pneu;
use App\Services\DocumentoFrete\DocumentoFreteService;
use Illuminate\Support\Facades\Log;
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

   Log::debug('TesteCommand executado com sucesso!');

   ProcessImportRowJob::dispatch();
   TesteJob::dispatch();

});


        
