<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Enum\ClienteEnum;
use App\Enum\MotivoDivergenciaViagem;
use App\Filament\Resources\Viagems\ViagemResource;
use App\Filament\Tables\SelectDocumentoFrete;
use App\Models\DocumentoFrete;
use App\Models\Viagem;
use App\Models\ViagemBugio;
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificacaoService as notify;
use Filament\Notifications\Notification;

class VincularDocumentoFreteAction
{
    public static function make(): Action
    {
        return Action::make('vincular_documento_frete')
            ->label('Vincular Documento de Frete')
            ->schema([
                ModalTableSelect::make('documento_frete_id')
                    ->relationship('documento', 'id')
                    ->tableConfiguration(SelectDocumentoFrete::class)
                    ->tableArguments(function (ViagemBugio $record): array {
                        return [
                            'veiculo_id' => $record->veiculo_id,
                        ];
                    })
                    ->afterStateUpdated(function (Set $set, $state) {
                        $documentoFrete = DocumentoFrete::find($state);
                        if ($documentoFrete) {
                            $set('documento_transporte', $documentoFrete->documento_transporte);
                            $set('numero_documento', $documentoFrete->numero_documento);
                        } else {
                            $set('documento_transporte', null);
                            $set('numero_documento', null);
                        }
                    }),
                TextInput::make('documento_transporte')
                    ->label('Documento de Transporte'),
                TextInput::make('numero_documento')
                    ->label('Número do Documento'),
            ])
            ->action(function (array $data, $record) {

                self::createViagemFromBugio($record, $data['documento_frete_id']);
            });
    }

    private static function createViagemFromBugio(ViagemBugio $record, $documentoFreteId): ?Viagem
    {
        $documentoFrete = DocumentoFrete::with('veiculo')->find($documentoFreteId);
        $veiculo = $documentoFrete?->veiculo;

        if (!$documentoFrete) {
            return null;
        }

        $data = [
            'veiculo_id'            => $documentoFrete->veiculo_id,
            'unidade_negocio'       => $veiculo->filial,
            'cliente'               => ClienteEnum::BUGIO->value,
            'numero_viagem'         => 'BG-' . $documentoFrete->documento_transporte,
            'documento_transporte'  => $documentoFrete->documento_transporte,
            'km_rodado'             => 0,
            'km_cadastro'             => 0,
            'km_cobrar'             => 0,
            'km_pago'               => $record->km_pago,
            'motivo_divergencia'    => MotivoDivergenciaViagem::SEM_OBS->value,
            'data_competencia'      => $record->data_competencia,
            'data_inicio'           => $record->data_competencia,
            'data_fim'              => $record->data_competencia,
            'conferido'             => false,
            'condutor'              => $record->condutor,
            'created_by'            => Auth::id(),
        ];

        $viagemService = new \App\Services\Viagem\ViagemService();
        $viagem = $viagemService->create($data);

        if (!$viagem) {
            notify::error('Erro ao criar viagem para o registro ID: ' . $record->id);
            return null;
        }


        $record->update([
            'documento_frete_id' => $documentoFreteId,
            'viagem_id' => $viagem->id,
        ]);

        Notification::make()
            ->title('Viagem BG-' . $documentoFrete->documento_transporte . ' criada com sucesso!')
            ->success()
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(ViagemResource::getUrl('view', ['record' => $viagem->id]))
                    ->openUrlInNewTab(),
                Action::make('undo')
                    ->color('gray'),
            ])
            ->send();

        return $viagem;
    }
}
