<?php

use App\Imports\DocumentoFreteImport;
use App\Services\DocumentoFrete\DocumentoFreteService;
use Illuminate\Support\Facades\Route;

Route::get('/teste', function () {
    $file = "01K3GQ73RG5NV1D35A0A4PJ7G2.xls";
    $importer = new DocumentoFreteImport();
    $service = new DocumentoFreteService();
    $service->importarRelatorioDocumentoFrete($importer, $file);
});

