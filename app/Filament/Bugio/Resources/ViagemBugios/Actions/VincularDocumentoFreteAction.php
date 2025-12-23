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
use App\Services\ViagemNumberService;
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
                    }),
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
            notify::error('Documento de Frete não encontrado para o ID: ' . $documentoFreteId);
            return null;
        }

        if($record->numero_sequencial === null){
            notify::alert('O registro de Viagem Bugio ID: ' . $record->id . ' não possui número sequencial. Gerando um novo número.');
            $service = new ViagemNumberService();
            $n = $service->next(ClienteEnum::BUGIO->prefixoViagem());
            $record->numero_sequencial = $n['numero_sequencial'];
            $record->save();
        }

        $destinos = ViagemBugio::query()
            ->where('numero_sequencial', $record->numero_sequencial)
            ->get()
            ->flatMap(fn($row) => collect($row['destinos'])->pluck('integrado_id'))
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values();

        $data = [
            'veiculo_id'            => $documentoFrete->veiculo_id,
            'unidade_negocio'       => $veiculo->filial,
            'cliente'               => ClienteEnum::BUGIO->value,
            'numero_viagem'         => 'BG-' . $record->numero_sequencial,
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

        notify::success('Viagem criada com sucesso! ID da Viagem: ' . $viagem->id);

        ViagemBugio::query()
            ->where('numero_sequencial', $record->numero_sequencial)
            ->update([
                'documento_frete_id' => $documentoFreteId,
                'viagem_id' => $viagem->id,
            ]);

        Notification::make()
            ->title('Viagem BG-' . $record->numero_sequencial . ' criada com sucesso!')
            ->success()
            ->send();

        return $viagem;
    }
}
