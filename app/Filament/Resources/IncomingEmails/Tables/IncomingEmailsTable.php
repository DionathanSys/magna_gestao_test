<?php

namespace App\Filament\Resources\IncomingEmails\Tables;

use App\Models\IncomingEmail;
use App\Services\MailInbound\InboundMessageIngestionService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IncomingEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['attachments', 'fiscalDocument.integrado']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('from_email')->label('Remetente')->searchable(),
                TextColumn::make('subject')->label('Assunto')->searchable()->wrap(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('attachments_count')->label('Anexos')->counts('attachments'),
                TextColumn::make('fiscalDocument.tipo_documento')->label('Tipo Doc.')->placeholder('-'),
                TextColumn::make('fiscalDocument.numero_nota')->label('Nota')->placeholder('-'),
                TextColumn::make('fiscalDocument.destinatario_documento')->label('Doc. Destino')->placeholder('-'),
                TextColumn::make('fiscalDocument.integrado.nome')->label('Integrado')->placeholder('-')->wrap(),
                TextColumn::make('pending_summary')->label('O que falta')->wrap(),
                TextColumn::make('received_at')->label('Recebido em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Filter::make('sem_documento_fiscal')
                    ->label('Nao parseados')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('fiscalDocument')),
                Filter::make('com_anexos_sem_grupo')
                    ->label('Anexos sem agrupamento')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereHas('attachments')
                        ->whereHas('fiscalDocument')
                        ->whereDoesntHave('fiscalDocument.saleGroups')
                        ->whereDoesntHave('fiscalDocument.remittanceGroups')),
            ])
            ->recordActions([
                Action::make('reprocessar_email')
                    ->label('Reprocessar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->action(function (IncomingEmail $record, InboundMessageIngestionService $service): void {
                        $service->reprocessStoredEmail($record->id);

                        Notification::make()
                            ->success()
                            ->title('Email reprocessado')
                            ->body("Email {$record->id} reprocessado manualmente.")
                            ->send();
                    }),
                ViewAction::make()->iconButton(),
            ]);
    }
}
