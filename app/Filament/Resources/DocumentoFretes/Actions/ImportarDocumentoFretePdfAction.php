<?php

namespace App\Filament\Resources\DocumentoFretes\Actions;

use App\Enum\ClienteEnum;
use App\Enum\Frete\TipoDocumentoEnum;
use App\Enum\Frete\TipoRelatorioDocumentoFreteEnum;
use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessarDocumentoFreteJob;
use Filament\Actions\Action;
use App\Services;
use App\Models;
use App\Services\DocumentoFrete\DocumentoFreteService;
use App\Services\NotificacaoService as notify;
use App\Services\Veiculo\VeiculoService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportarDocumentoFretePdfAction
{
    private const DISK = 'local';

    public static function make(): Action
    {
        return Action::make('importar-documento-frete-pdf')
            ->label('Importar Espelho Frete')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('documento_frete')
                    ->label('Documento Frete')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(5120)
                    ->disk(self::DISK)
                    ->directory('temp-documento-fretes')
                    ->storeFiles(false)
                    ->required()
                    ->downloadable()
                    ->previewable()
                    ->helperText('Tamanho máximo: 5MB. Formato: PDF.'),
                Select::make('cliente')
                    ->label('Cliente')
                    ->native(false)
                    ->options(ClienteEnum::toSelectArray())
                    ->required(),
            ])
            ->action(
                function (array $data, Action $action) {

                    try {

                        dd($data);

                        Log::info('Iniciando importação de Documento Frete PDF', [
                            'cliente' => $data['cliente'],
                            'arquivo_path' => $data['documento_frete'],
                        ]);

                        $relativePath = $data['documento_frete'];

                        $fullPath = Storage::disk(self::DISK)->path($relativePath);

                        if (!file_exists($fullPath)) {
                            throw new \Exception('Arquivo não encontrado: ' . $fullPath);
                        }

                        Log::info('Arquivo localizado', [
                            'relative_path' => $relativePath,
                            'full_path' => $fullPath,
                            'file_size' => filesize($fullPath),
                        ]);

                        if (!self::validate($data)) {
                            Log::warning('Validação falhou para importação de Documento Frete', [
                                'data' => $data,
                            ]);
                            notify::error('Validação dos dados para importação de Documento Frete falhou.');
                            $action->halt();
                            return;
                        }

                        self::processar($fullPath, $data['cliente']);

                        notify::success('Importação de Documento Frete iniciada com sucesso.');
                    } catch (\Exception $e) {
                        Log::error('Erro ao iniciar importação de Documento Frete PDF', [
                            'exception' => $e->getMessage(),
                            'data' => $data,
                        ]);
                        notify::error('Erro ao iniciar importação de Documento Frete: ' . $e->getMessage());
                        $action->halt();
                    }
                }
            );
    }

    private static function processar(string $filePath, string $cliente): void
    {
        $importer = new \App\Services\Import\Importers\ViagemEspelhoFreteImporter();

        $data = $importer->handle($filePath);

        $data = collect($data);

        $data->each(function ($frete) use ($cliente) {

            if (!($veiculo_id = (new VeiculoService())->getVeiculoIdByPlaca($frete['placa']))) {
                Log::warning('Veículo não encontrado para a placa informada.', [
                    'placa' => $frete['placa']
                ]);
                return;
            }

            $docFrete = [
                'veiculo_id'           => $veiculo_id,
                'parceiro_origem'      => $cliente,
                'parceiro_destino'     => trim(preg_replace('/^\d+\s*-\s*/', '', $frete['destino'])),
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
    }

    private static function validate(array $data): bool
    {
        Log::debug('Validando dados para importação de Documento Frete', [
            'data' => $data,
            'clientes_validos' => ClienteEnum::toSelectArray(),
        ]);

        $validate = Validator::make($data, [
            'documento_frete' => 'required|file|mimes:pdf|max:5120',
            'cliente' => 'required|in:' . implode(',', ClienteEnum::toSelectArray()),
        ], [
            'documento_frete.required' => 'O campo Documento Frete é obrigatório.',
            'documento_frete.file' => 'O campo Documento Frete deve ser um arquivo válido.',
            'documento_frete.mimes' => 'O campo Documento Frete deve ser um arquivo do tipo: pdf.',
            'documento_frete.max' => 'O campo Documento Frete não deve ser maior que 5MB.',
            'cliente.required' => 'O campo Cliente é obrigatório.',
            'cliente.in' => 'O campo Cliente selecionado é inválido.',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            foreach ($errors as $error) {
                notify::error($error);
            }
            return false;
        }

        return true;
    }
}
