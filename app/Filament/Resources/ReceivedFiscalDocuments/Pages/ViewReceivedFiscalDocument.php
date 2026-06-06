<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments\Pages;

use App\Filament\Resources\ReceivedFiscalDocuments\ReceivedFiscalDocumentResource;
use App\Models\Integrado;
use App\Services\MailInbound\LinkFiscalDocumentToIntegradoService;
use App\Services\MailInbound\ShipmentTripService;
use App\Services\MailInbound\ShipmentDocumentMatcher;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivedFiscalDocument extends ViewRecord
{
    protected static string $resource = ReceivedFiscalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('vincular_integrado')
                ->label('Vincular Integrado')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn (): bool => $this->record->tipo_documento === 'remittance')
                ->schema([
                    TextInput::make('destinatario_nome_xml')
                        ->label('Nome no XML')
                        ->default(fn (): ?string => $this->record->destinatario_nome)
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('destinatario_documento_xml')
                        ->label('Documento no XML')
                        ->default(fn (): ?string => $this->record->destinatario_documento)
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('integrado_id')
                        ->label('Integrado equivalente')
                        ->options(fn () => Integrado::query()->orderBy('nome')->pluck('nome', 'id')->toArray())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data, LinkFiscalDocumentToIntegradoService $service): void {
                    $integrado = Integrado::query()->findOrFail($data['integrado_id']);

                    $service->handle($this->record, $integrado);

                    Notification::make()
                        ->success()
                        ->title('Integrado vinculado')
                        ->body("Documento fiscal {$this->record->id} vinculado ao integrado {$integrado->nome}.")
                        ->send();
                }),
            Action::make('reprocessar_documento')
                ->label('Reprocessar Documento')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function (ShipmentDocumentMatcher $matcher, ShipmentTripService $shipmentTripService): void {
                    $group = $matcher->match($this->record->fresh());

                    if ($group) {
                        $shipmentTripService->createFromGroup($group->id);
                    }

                    Notification::make()
                        ->success()
                        ->title('Documento reprocessado')
                        ->body("Documento fiscal {$this->record->id} reavaliado para pareamento.")
                        ->send();
                }),
        ];
    }
}
