<?php

namespace App\Filament\Resources\DocumentoFretes\Actions;

use App\Enum\ClienteEnum;
use App\Enum\Frete\TipoRelatorioDocumentoFreteEnum;
use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessarDocumentoFreteJob;
use Filament\Actions\Action;
use App\Services;
use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportarDocumentoFretePdfAction
{
    public static function make(): Action
    {
        return Action::make('importar-documento-frete-pdf')
            ->label('Importar Espelho Frete')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('documento_frete')
                    ->label('Documento Frete')
                    ->required(),
                Select::make('cliente')
                    ->label('Cliente')
                    ->native(false)
                    ->options(ClienteEnum::toSelectArray())
                    ->required(),
            ])
            ->action(function (array $data, Action $action) {
                Log::info('Iniciando importação de Documento Frete', [
                    'data' => $data,
                ]);

                if (!self::validate($data)) {
                    Log::warning('Validação falhou para importação de Documento Frete', [
                        'data' => $data,
                    ]);
                    notify::error('Validação dos dados para importação de Documento Frete falhou.');
                    $action->halt();
                    return;
                }

                $file = $data['documento_frete'];
                dd($file);
                Log::debug(__METHOD__ . '@' . __LINE__, [
                    'file' => $file
                ]);

                notify::success('Importação de Documento Frete iniciada com sucesso.');
            });
    }

    private static function processar()
    {

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
