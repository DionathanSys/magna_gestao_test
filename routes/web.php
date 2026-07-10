<?php

use App\Enum\Frete\TipoDocumentoEnum;
use App\Filament\Resources\DocumentoFretes\DocumentoFreteResource;
use App\Models\IncomingEmailAttachment;
use App\Models\OrdemServico;
use App\Models\Veiculo;
use App\Services\DocumentoFrete\DocumentoFreteService;
use App\Services\Import\Importers\ViagemEspelhoFreteImporter;
use App\Services\OrdemServico\OrdemServicoPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/ordem-servico/{ordemServico}/pdf', function (OrdemServico $ordemServico) {
    $service = new OrdemServicoPdfService;

    return $service->visualizarPdfOrdemServico($ordemServico);
})->name('ordem-servico.pdf.visualizar');

Route::get('/import-pdf', function () {
    return view('importPdf');
})->name('import.pdf');

Route::post('/upload-pdf', function (Request $request) {

    $request->validate([
        'pdfFile' => 'required|file|mimes:pdf|max:2048',
    ]);

    $file = $request->file('pdfFile');

    Log::debug(__METHOD__.'@'.__LINE__, [
        'file' => $file,
    ]);

    $importer = new ViagemEspelhoFreteImporter;

    $data = $importer->handle($file->getRealPath());

    $data = collect($data);

    $data->each(function ($frete) {
        Log::debug('Frete ', [
            'data' => $frete,
        ]);

        if (! ($veiculo_id = Veiculo::where('placa', $frete['placa'])->first()?->id)) {
            Log::warning('Veículo não encontrado para a placa informada.', [
                'placa' => $frete['placa'],
            ]);

            return;
        }

        $docFrete = [
            'veiculo_id' => $veiculo_id,
            'parceiro_origem' => 'BRF S.A. CHAPECO/SC',
            'parceiro_destino' => trim(preg_replace('/^\d+\s*-\s*/', '', $frete['destino'])),
            'documento_transporte' => $frete['doc_transporte'],
            'numero_documento' => $frete['nfe'],
            'data_emissao' => $frete['data_emissao'],
            'valor_total' => $frete['valor'],
            'valor_icms' => 0,
            'tipo_documento' => TipoDocumentoEnum::NFS,
        ];

        try {
            $documentoFreteService = new DocumentoFreteService;
            $documentoFreteService->criarDocumentoFrete($docFrete);
            Log::info('Documento de frete criado com sucesso.', [
                'data' => $docFrete,
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao criar documento de frete', [
                'error' => $e->getMessage(),
                'data' => $docFrete,
            ]);
        }

    });

    echo '<h2>Importação concluída!</h2>';
    echo '<a href="'.DocumentoFreteResource::getUrl().'">Acessar Documentos de Frete</a>';
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

Route::get('/admin/attachments/{attachment}/view', function (IncomingEmailAttachment $attachment) {
    if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
        abort(404);
    }

    return response()->file(
        Storage::disk($attachment->disk)->path($attachment->path),
        ['Content-Type' => $attachment->mime_type ?: 'application/pdf'],
    );
})->middleware('auth')->name('attachments.view');

Route::get('/teste-job', function () {

    // crie na sessão um valor true ou false para mudar o estado de groupOnly na table de CargaViagems
    // cada vez que acessar essa rota, o valor será invertido
    $current = session()->get('cargaViagemsGroupOnly', false);
    session()->put('cargaViagemsGroupOnly', ! $current);
    Log::debug('Toggle cargaViagemsGroupOnly to '.(! $current), [
        'metodo' => __METHOD__.'@'.__LINE__,
        'new_value' => ! $current,
    ]);
    echo 'cargaViagemsGroupOnly set to '.(! $current);
});
