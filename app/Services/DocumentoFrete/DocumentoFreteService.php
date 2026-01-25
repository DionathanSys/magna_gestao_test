<?php

namespace App\Services\DocumentoFrete;

use App\Contracts\XlsxImportInterface;
use App\Jobs\ProcessXlsxRowJob;
use App\{Models, Services};
use App\Jobs\VincularRegistroResultadoJob;
use App\Jobs\VincularViagemDocumentoFrete;
use App\Models\Viagem;
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

    public function criarDocumentoFrete(array $dados)
    {

        try {

            $action = new Actions\RegistrarDocumentoFrete();
            $documentoFrete = $action->handle($dados);

            if (!$documentoFrete) {
                $this->setWarning('Documento de frete não foi registrado.');
                VincularViagemDocumentoFrete::dispatch($dados['documento_transporte']);
                return;
            }

            //TODO: Devido alteração na regra de negócio, o job de vinculação do registro de resultado foi comentado temporariamente.
            // VincularRegistroResultadoJob::dispatch($documentoFrete->id, Models\DocumentoFrete::class);

            // Log::info('Job de vinculação de registro de resultado despachado para documento de frete ID: ' . $documentoFrete->id, [
            //     'metodo' => __METHOD__ . '@' . __LINE__,
            // ]);

            $this->setSuccess('Documento registrado com sucesso.');

            VincularViagemDocumentoFrete::dispatch($dados['documento_transporte']);

            return $documentoFrete;
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

    public function vincularDocumentoFrete(int $documentoTransporte, int|null $viagemId = null): void
    {
        try {

            Log::debug(__METHOD__ . '@' . __LINE__, [
                'documento_transporte' => $documentoTransporte,
                'viagem_id' => $viagemId,
            ]);
            
            if (!$viagemId) {
                $viagemId = Viagem::query()
                    ->where('documento_transporte', $documentoTransporte)
                    ->value('id');
            }

            if(!$viagemId) {
                Log::warning('Viagem não encontrada para vinculação do documento de frete', [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'documento_transporte' => $documentoTransporte,
                ]);
                $this->setError("Viagem não encontrada para o documento de transporte {$documentoTransporte}.");
                return;
            }

            $action = new Actions\VincularViagemDocumento();
            $documentosFreteAtualizados = $action->handle($documentoTransporte, $viagemId);

            Log::info('Vinculação do documento de frete à viagem concluída', [
                'metodo'                => __METHOD__ . '@' . __LINE__,
                'documento_transporte'  => $documentoTransporte,
                'viagem_id'             => $viagemId,
                'documentos_frete_atualizados' => $documentosFreteAtualizados,
            ]);

            if (!$documentosFreteAtualizados) {
                $this->setError("Falha ao vincular documento de frete à viagem {$viagemId}.");
                return;
            }

            $this->setSuccess("Documento de frete vinculado à viagem {$viagemId} com sucesso.");

        } catch (\Exception $e) {
            Log::error(__METHOD__ . '@' . __LINE__, [
                'error' => $e->getMessage(),
                'documento_transporte' => $documentoTransporte,
            ]);
            $this->setError('Falha ao vincular documento de frete à viagem', [
                'metodo' => __METHOD__,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function createViagemNutrepampaFromDocumentoFrete(Collection $documentosFrete)
    {
        $action = new Actions\GerarViagemNutrepampaFromDocumento($documentosFrete);
        return $action->handle();

    }

    
}
