<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments\Tables;

use App\Jobs\MailInbound\CreateTripFromShipmentDocumentsJob;
use App\Models\Integrado;
use App\Models\ReceivedFiscalDocument;
use App\Services\MailInbound\LinkFiscalDocumentToIntegradoService;
use App\Services\MailInbound\ShipmentDocumentMatcher;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceivedFiscalDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['incomingEmail', 'integrado', 'saleGroups', 'remittanceGroups']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('tipo_documento')->label('Tipo')->badge(),
                TextColumn::make('numero_nota')->label('Nota')->searchable(),
                TextColumn::make('emitente_documento')->label('Doc. Emissor')->searchable(),
                TextColumn::make('destinatario_documento')->label('Doc. Destino')->searchable(),
                TextColumn::make('integrado.nome')->label('Integrado')->placeholder('-')->wrap(),
                TextColumn::make('pending_summary')->label('O que falta')->wrap(),
                TextColumn::make('incomingEmail.subject')->label('Email')->toggleable()->wrap(),
                TextColumn::make('emitido_em')->label('Emissao')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo_documento')
                    ->options([
                        'sale' => 'Sale',
                        'remittance' => 'Remittance',
                        'unknown' => 'Unknown',
                    ]),
            ])
            ->recordActions([
                Action::make('vincular_integrado')
                    ->label('Vincular Integrado')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->iconButton()
                    ->visible(fn (ReceivedFiscalDocument $record): bool => $record->tipo_documento === 'remittance')
                    ->schema([
                        TextInput::make('destinatario_nome_xml')
                            ->label('Nome no XML')
                            ->default(fn (ReceivedFiscalDocument $record): ?string => $record->destinatario_nome)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('destinatario_documento_xml')
                            ->label('Documento no XML')
                            ->default(fn (ReceivedFiscalDocument $record): ?string => $record->destinatario_documento)
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('integrado_id')
                            ->label('Integrado equivalente')
                            ->options(fn () => Integrado::query()->orderBy('nome')->pluck('nome', 'id')->toArray())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (ReceivedFiscalDocument $record, array $data, LinkFiscalDocumentToIntegradoService $service): void {
                        $integrado = Integrado::query()->findOrFail($data['integrado_id']);

                        $service->handle($record, $integrado);

                        Notification::make()
                            ->success()
                            ->title('Integrado vinculado')
                            ->body("Documento fiscal {$record->id} vinculado ao integrado {$integrado->nome}.")
                            ->send();
                    }),
                Action::make('reprocessar_documento')
                    ->label('Reprocessar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->action(function (ReceivedFiscalDocument $record, ShipmentDocumentMatcher $matcher): void {
                        $group = $matcher->match($record->fresh());

                        if ($group) {
                            CreateTripFromShipmentDocumentsJob::dispatch($group->id)
                                ->onQueue(config('mail-inbound.queue.trip'));
                        }

                        Notification::make()
                            ->success()
                            ->title('Documento reprocessado')
                            ->body("Documento fiscal {$record->id} reavaliado para pareamento.")
                            ->send();
                    }),
                ViewAction::make()->iconButton(),
            ]);
    }
}
