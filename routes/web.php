<?php

use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessImportRowJob;
use App\Jobs\TesteJob;
use App\Models\Pneu;
use App\Services\DocumentoFrete\DocumentoFreteService;
use App\Traits\PdfExtractorTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Spatie\PdfToText\Pdf as PdfToTextPdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

Route::get('/import-pdf', function () {
    return view('importPdf');
})->name('import.pdf');

Route::post('/upload-pdf', function (\Illuminate\Http\Request $request) {



    $request->validate([
        'pdfFile' => 'required|file|mimes:pdf|max:2048',
    ]);

    $file = $request->file('pdfFile');

    $importer = new \App\Services\Import\Importers\ViagemEspelhoFreteImporter();

    $importer->handle($file->getRealPath());

    $text = $importer->extractPdfData($file);

    // Processar o texto e extrair dados estruturados
    $data = $importer->processPdfText($text);

    dd($data);
    // Separar linhas
    $lines = explode("\r\n", $text);

    // Preparar array para armazenar os dados
    $data = [];

    $current = [];

    // 1. Substituir os padrões pelo que quisermos
    $substituicoes = [
        'NFE:' => '#NFE#',
        'Chave de acesso:' => '#CHAVE#',
        'Doc.Transporte:' => '#DOC#',
        'Placa:' => '#PLACA#',
        'R$' => '#VALOR#',
    ];

    dump($substituicoes);
    dump($lines);
    dd($text);
    // Fazendo substituições no texto
    foreach ($substituicoes as $original => $replace) {
        $text = str_replace($original, $replace, $text);
    }

    // 2. Separar em linhas
    $lines = array_map('trim', explode("\n", $text));

    // 3. Preparar array
    $data = [];
    $current = [];

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        if (strpos($line, '#NFE#') !== false) {
            preg_match('/#NFE#\s*(\d+)/', $line, $matches);
            $current['nfe'] = $matches[1] ?? null;
        }

        if (strpos($line, '#CHAVE#') !== false) {
            preg_match('/#CHAVE#\s*(\d+)/', $line, $matches);
            $current['chave_acesso'] = $matches[1] ?? null;
        }

        if (strpos($line, '#DOC#') !== false) {
            preg_match('/#DOC#\s*(\d+)/', $line, $matches);
            $current['doc_transporte'] = $matches[1] ?? null;
            $current['valor'] = (float) str_replace(',', '.', $lines[$i + 2]) ?? 0;
        }

        if (strpos($line, '#PLACA#') !== false) {
            preg_match('/#PLACA#\s*(\w+)/', $line, $matches);
            $current['placa'] = $matches[1] ?? null;

            if (key_exists($current['doc_transporte'] . '-1', $data)) {
                if ($data[$current['doc_transporte'] . '-1']['valor'] == $current['valor']) {
                    $current['valor'] = 0;
                }
                $data[$current['doc_transporte'] . '-2'] = $current;
            } else {
                $data[$current['doc_transporte'] . '-1'] = $current;
            }

            $current = [];
        }
    }

    $data = collect($data);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Definir cabeçalhos
    $sheet->setCellValue('A1', 'NFE');
    $sheet->setCellValue('B1', 'Chave de Acesso');
    $sheet->setCellValue('C1', 'Doc Transporte');
    $sheet->setCellValue('D1', 'Placa');
    $sheet->setCellValue('E1', 'Valor');

    // Preencher dados
    $row = 2;
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $row, $item['nfe']);
        $sheet->setCellValue('B' . $row, $item['chave_acesso']);
        $sheet->setCellValue('C' . $row, $item['doc_transporte']);
        $sheet->setCellValue('D' . $row, $item['placa']);
        $sheet->setCellValue('E' . $row, $item['valor']);
        $row++;
    }

    // Criar arquivo para download
    $response = new StreamedResponse(function () use ($spreadsheet) {
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    });

    // Configurar cabeçalhos para download
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment;filename="espelho frete.xlsx"');
    $response->headers->set('Cache-Control', 'max-age=0');

    return $response;
})->name('upload.pdf');

