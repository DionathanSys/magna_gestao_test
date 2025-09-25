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

    $viagemDataCompleta = [
    'veiculo_id'            => 2,
    'numero_viagem'         => '21522246',
    'documento_transporte'  => '129985668',
    'km_rodado'             => 81.0,
    'km_pago'               => 0.0,
    'km_cadastro'           => 79.07,
    'km_cobrar'             => 0.0,
    'motivo_divergencia'    => 'SEM OBSERVAÇÃO',
    'data_competencia'      => '2025-09-23',
    'data_inicio'           => '2025-09-23 00:34',
    'data_fim'              => '2025-09-23 05:33',
    'conferido'             => false,
    'created_by'            => 2,
    'updated_by'            => 2,
];

    // Criar diretamente
    $viagem = \App\Models\Viagem::create($viagemDataCompleta);

});
