<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Enum\ClienteEnum;
use App\Models\DocumentoFrete;
use App\Models\ViagemBugio;
use App\Services\ViagemBugio\ViagemBugioService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InserirNumeroDocumentoAction
{
    public static function make(): Action
    {
        return Action::make('inserir_nro_documento')
            ->label('Inserir Nº CTe')
            ->schema([
                TextInput::make('nro_documento')
                    ->label('Nro. CTe emitido')
                    ->required()
                    ->rule('numeric')
                    ->rule('min:1')
                    ->afterStateUpdated(fn($state, $set) => $set('nro_documento', trim($state)))
                    ->live(onBlur: true),
                DatePicker::make('data_emissao')
                    ->label('Data de Emissão')
                    ->displayFormat('d/m/Y')
                    ->required()
                    ->default(now())
                    ->maxDate(now()),
            ])
            ->color('success')
            ->iconButton()
            ->icon(Heroicon::ClipboardDocumentCheck)
            ->disabled(fn(ViagemBugio $record) => $record->nro_documento != null)
            ->action(function (ViagemBugio $record, array $data) {
                // Remover espaços em branco do número do documento
                $nroDocumento = trim($data['nro_documento']);

                // Validar se o número é válido
                if (empty($nroDocumento) || !is_numeric($nroDocumento) || $nroDocumento < 1) {
                    Notification::make()
                        ->danger()
                        ->title('Número inválido')
                        ->body('O número do CTe deve ser um valor numérico maior que zero.')
                        ->send();
                    return;
                }

                // Extrair tipo_documento do info_adicionais
                $infoAdicionais = is_string($record->info_adicionais) 
                    ? json_decode($record->info_adicionais, true) 
                    : $record->info_adicionais;
                
                Log::debug('Info Adicionais da ViagemBugio', ['info_adicionais' => $infoAdicionais]);

                $tipoDocumento = $infoAdicionais['tipo_documento'] ?? null;

                if (!$tipoDocumento) {
                    Notification::make()
                        ->danger()
                        ->title('Tipo de documento não encontrado')
                        ->body('Não foi possível identificar o tipo de documento.')
                        ->send();
                    return;
                }

                // Definir parceiros usando o enum ClienteEnum
                $parceiroOrigem = ClienteEnum::BUGIO->value;
                $parceiroDestino = ClienteEnum::BUGIO_NUTRI->value;

                // Verificar se já existe um documento com os mesmos dados
                $documentoExistente = DocumentoFrete::query()
                    ->where('numero_documento', $nroDocumento)
                    ->where('parceiro_origem', $parceiroOrigem)
                    ->where('parceiro_destino', $parceiroDestino)
                    ->where('tipo_documento', $tipoDocumento)
                    ->first();

                if ($documentoExistente) {
                    Notification::make()
                        ->warning()
                        ->title('Documento já existe')
                        ->body("Já existe um documento de frete com o número {$nroDocumento} e as mesmas informações (ID: {$documentoExistente->id}).")
                        ->send();
                    return;
                } else {
                    Log::debug('Nenhum documento existente encontrado com os mesmos dados.', [
                        'numero_documento' => $nroDocumento,
                        'parceiro_origem' => $parceiroOrigem,
                        'parceiro_destino' => $parceiroDestino,
                        'tipo_documento' => $tipoDocumento,
                    ]);
                }

                // Atualizar ViagemBugio com o número do documento
                $record->update([
                    'nro_documento' => $nroDocumento,
                ]);

                Log::info("Nro. CTe {$nroDocumento} inserido para ViagemBugio ID {$record->id} pelo usuário " . Auth::user()->name, [
                    'record' => $record->toArray(),
                    'data_emissao' => $data['data_emissao'],
                ]);

                // Criar viagem e documento de frete a partir do Bugio
                $bugioService = new ViagemBugioService();
                $bugioService->createViagemFromBugio($record, $data['data_emissao']);

                Notification::make()
                    ->success()
                    ->title('CTe inserido com sucesso')
                    ->body("O CTe {$nroDocumento} foi registrado e a viagem foi criada.")
                    ->send();
            });
    }
}
