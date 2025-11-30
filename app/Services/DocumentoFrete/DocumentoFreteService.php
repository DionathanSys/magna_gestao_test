<?php

namespace App\Services\DocumentoFrete;

use App\Contracts\XlsxImportInterface;
use App\Jobs\ProcessXlsxRowJob;
use App\{Models, Services};
use App\Jobs\VincularRegistroResultadoJob;
use App\Jobs\VincularViagemDocumentoFrete;
use App\Services\DocumentoFrete\Actions\VincularViagemDocumento;
use App\Traits\ServiceResponseTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DocumentoFreteService
{

    use ServiceResponseTrait;

    protected array $firstRowData = [];

    public function __construct() {}

    public function criarDocumentoFrete(array $dados): void
    {

        try {

            $action = new Actions\RegistrarDocumentoFrete();
            $documentoFrete = $action->handle($dados);

            if (!$documentoFrete) {
                $this->setWarning('Documento de frete não foi registrado.');
                VincularViagemDocumentoFrete::dispatch($dados['documento_transporte']);
                return;
            }

            VincularRegistroResultadoJob::dispatch($documentoFrete->id, Models\DocumentoFrete::class);

            Log::info('Job de vinculação de registro de resultado despachado para documento de frete ID: ' . $documentoFrete->id, [
                'metodo' => __METHOD__ . '@' . __LINE__,
            ]);

            $this->setSuccess('Documento registrado com sucesso.');

            VincularViagemDocumentoFrete::dispatch($dados['documento_transporte']);
        } catch (\Exception $e) {

            Log::error('Erro ao criar documento de frete.', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'dados' => $dados,
                'error' => $e->getMessage()
            ]);

            $this->setError('Erro ao criar documento de frete', [
                'error' => $this->getData(),
            ]);
        }
    }

    public function importarRelatorioDocumentoFrete(XlsxImportInterface $importer, string $fileName): void
    {
        try {

            $isWindows = PHP_OS_FAMILY === 'Windows';
            $dir = $isWindows ? 'app\\private\\' : 'app/private/';
            $filePath = storage_path($dir . $fileName);

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $rowIterator = $worksheet->getRowIterator();

            // Processar primeira linha (header)
            if (!$rowIterator->valid()) {
                throw new \Exception('Arquivo vazio.');
            }

            $firstRow = $rowIterator->current();
            $cellIterator = $firstRow->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $this->firstRowData = [];
            foreach ($cellIterator as $cell) {
                $this->firstRowData[] = $cell->getValue();
            }

            if (empty($this->firstRowData)) {
                throw new \Exception('Header vazio.');
            }

            Log::debug('Header extraído', [
                'header' => $this->firstRowData,
            ]);

            $importer->validateColumns($this->firstRowData);

            // Avançar para a próxima linha (pular o header)
            $rowIterator->next();
            $linhaCount = 0;

            // Processar as demais linhas
            while ($rowIterator->valid()) {
                $row = $rowIterator->current();
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                // Verificar se a linha não está vazia
                if (empty(array_filter($rowData))) {
                    $rowIterator->next();
                    continue;
                }

                // Verificar se o número de colunas confere
                if (count($this->firstRowData) !== count($rowData)) {
                    Log::warning('Número de colunas não confere', [
                        'header_count' => count($this->firstRowData),
                        'row_count' => count($rowData),
                        'linha' => $linhaCount + 2,
                    ]);
                    $rowIterator->next();
                    continue;
                }

                // Criar array associativo
                $rowAssociativo = array_combine($this->firstRowData, $rowData);

                if ($rowAssociativo === false) {
                    Log::error('Falha ao combinar header com dados', [
                        'header' => $this->firstRowData,
                        'row' => $rowData,
                        'linha' => $linhaCount + 2,
                    ]);
                    $rowIterator->next();
                    continue;
                }

                Log::debug('Linha processada', [
                    'linha' => $linhaCount + 2,
                    'dados' => $rowAssociativo,
                ]);

                // Enviar para o Job com dados mapeados
                ProcessXlsxRowJob::dispatch($rowAssociativo, get_class($importer));

                $linhaCount++;
                $rowIterator->next();
            }
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'first_row_data' => $this->firstRowData,
            ]);
            $this->setError($e->getMessage());
            return;
        }

        $this->setSuccess('Registrado arquivo para importação.');
        return;
    }

    public function vincularDocumentoFrete(int $documentoTransporte): ?Models\DocumentoFrete
    {
        try {

            $queries = new \App\Services\DocumentoFrete\Queries\GetDocumentoFrete([
                'sem_vinculo_viagem' => true
            ]);

            $documentoFrete = $queries->byDocumentoTransporte($documentoTransporte);

            if (!$documentoFrete) {
                $this->setError("Documento de frete com número de transporte {$documentoTransporte} não encontrado ou já vinculado a uma viagem.");
                return null;
            }

            $queriesViagem = new \App\Services\Viagem\Queries\GetViagem();
            $viagem = $queriesViagem->byDocumentoTransporte($documentoTransporte);

            if (!$viagem) {
                $this->setError("Viagem com número de transporte {$documentoTransporte} não encontrada.");
                return null;
            }

            $action = new Actions\VincularViagemDocumento();
            $documentoFrete = $action->handle($documentoFrete, $viagem);

            if (!$documentoFrete) {
                $this->setError("Falha ao vincular documento de frete à viagem {$viagem->numero_viagem}.");
                return null;
            }

            $this->setSuccess("Documento de frete vinculado à viagem {$viagem->id} com sucesso.");
            return $documentoFrete;
        } catch (\Exception $e) {
            Log::error(__METHOD__ . '@' . __LINE__, [
                'error' => $e->getMessage(),
                'documento_transporte' => $documentoTransporte,
            ]);
            $this->setError('Falha ao vincular documento de frete à viagem', [
                'metodo' => __METHOD__,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function createViagemFromDocumentoFrete(Collection $documentosFrete)
    {
        $viagemService = new Services\Viagem\ViagemService();

        $data = $documentosFrete->groupBy(['veiculo_id', function (array $item) {
            return $item['data_emissao'];
        }]);

        Log::debug('Dados agrupados por veículo para criação de viagem', [
            'data' => $data,
        ]);

        $data->each(function ($docsFrete, $veiculoId) use (&$viagemService) {
            Log::debug('Processando veículo ID: ' . $veiculoId, [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'documentos_frete' => $docsFrete,
            ]);

            $viagemData = $this->processDataToCreateViagem($docsFrete->sortBy('numero_documento'));


        });



        try {
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
            ]);
            $this->setError($e->getMessage());
            return null;
        }
    }

    private function processDataToCreateViagem(Collection $documentosFrete): array
    {

       if($documentosFrete->isEmpty()) {
            Log::warning('Nenhum documento de frete fornecido para criação de viagem.');
            return [];
        }

        $documentosFrete->each(function ($docsFrete) {
            Log::debug('Documento de frete para viagem', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'documento_frete' => $docsFrete,
                'countDocs' => $docsFrete->count(),
                'firstDoc' => $docsFrete->first(),
                'lastDoc' => $docsFrete->last(),
                'dif'   => $docsFrete->last()->numero_documento - $docsFrete->first()->numero_documento,
            ]);
        });
        die();
return [];
        $firstDoc = $documentosFrete->first();
        $lastDoc = $documentosFrete->last();

        $dataViagem = [
            'veiculo_id' => $firstDoc->veiculo_id,
            'data_inicio' => $firstDoc->data_emissao,
            'data_fim' => $lastDoc->data_emissao,
            'documento_transporte' => $firstDoc->documento_transporte,
        ];

        Log::info('Dados processados para criação de viagem', [
            'dados_viagem' => $dataViagem,
        ]);

        return $dataViagem;
    }
}
