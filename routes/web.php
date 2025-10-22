<?php

use App\Enum\Frete\TipoDocumentoEnum;
use App\Filament\Resources\DocumentoFretes\DocumentoFreteResource;
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

    $data = $importer->handle($file->getRealPath());

    $data = collect($data);

    $data->each(function ($frete) {
        Log::debug('Frete ', [
            'data' => $frete
        ]);

        if(!($veiculo_id = \App\Models\Veiculo::where('placa', $frete['placa'])->first()?->id)) {
            Log::warning('Veículo não encontrado para a placa informada.', [
                'placa' => $frete['placa']
            ]);
            return;
        }

        $docFrete = [
            'veiculo_id'           => $veiculo_id,
            'parceiro_destino'     => 'BRF S.A. CHAPECO/SC',
            'parceiro_origem'      => trim(preg_replace('/^\d+\s*-\s*/', '', $frete['destino'])),
            'documento_transporte' => $frete['doc_transporte'],
            'numero_documento'     => $frete['nfe'],
            'data_emissao'         => $frete['data_emissao'],
            'valor_total'          => $frete['valor'],
            'valor_icms'           => 0,
            'tipo_documento'       => TipoDocumentoEnum::NFS,
        ];

        try {
            $documentoFreteService = new DocumentoFreteService();
            $documentoFreteService->criarDocumentoFrete($docFrete);
            Log::info('Documento de frete criado com sucesso.', [
                'data' => $docFrete
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar documento de frete', [
                'error' => $e->getMessage(),
                'data' => $docFrete
            ]);
        }

    });
    
    echo '<h2>Importação concluída!</h2>';
    echo '<a href="' . DocumentoFreteResource::getUrl() . '">Acessar Documentos de Frete</a>';
    // $spreadsheet = new Spreadsheet();
    // $sheet = $spreadsheet->getActiveSheet();

    // // Definir cabeçalhos
    // $sheet->setCellValue('A1', 'NFE');
    // $sheet->setCellValue('B1', 'Chave de Acesso');
    // $sheet->setCellValue('C1', 'Doc Transporte');
    // $sheet->setCellValue('D1', 'Placa');
    // $sheet->setCellValue('E1', 'Valor');

    // // Preencher dados
    // $row = 2;
    // foreach ($data as $item) {
    //     $sheet->setCellValue('A' . $row, $item['nfe']);
    //     $sheet->setCellValue('B' . $row, $item['chave_acesso']);
    //     $sheet->setCellValue('C' . $row, $item['doc_transporte']);
    //     $sheet->setCellValue('D' . $row, $item['placa']);
    //     $sheet->setCellValue('E' . $row, $item['valor']);
    //     $row++;
    // }

    // // Criar arquivo para download
    // $response = new StreamedResponse(function () use ($spreadsheet) {
    //     $writer = new Xlsx($spreadsheet);
    //     $writer->save('php://output');
    // });

    // // Configurar cabeçalhos para download
    // $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    // $response->headers->set('Content-Disposition', 'attachment;filename="espelho frete.xlsx"');
    // $response->headers->set('Cache-Control', 'max-age=0');

    // return $response;
})->name('upload.pdf');

