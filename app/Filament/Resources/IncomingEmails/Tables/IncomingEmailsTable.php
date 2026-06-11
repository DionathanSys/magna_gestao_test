<?php

namespace App\Filament\Resources\IncomingEmails\Tables;

use App\Jobs\MailInbound\ProcessIncomingBugioCteReturnEmailJob;
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
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['attachments', 'fiscalDocument.integrado'])
                ->withExists(['cteReturnMessages as tem_retorno_cte']))
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
                TextColumn::make('tem_retorno_cte')
                    ->label('Retorno CTe')
                    ->badge()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Vinculado' : 'Nao mapeado')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
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
                    ->label('Reprocessar Fiscal')
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
                Action::make('processar_retorno_cte')
                    ->label('Retorno CTe')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('info')
                    ->iconButton()
                    ->action(function (IncomingEmail $record): void {
                        ProcessIncomingBugioCteReturnEmailJob::dispatch($record->id)
                            ->onQueue(config('mail-inbound.queue.cte_return'));

                        Notification::make()
                            ->success()
                            ->title('Retorno CT-e enfileirado')
                            ->body("Email {$record->id} enviado para matching de retorno CT-e.")
                            ->send();
                    }),
                ViewAction::make()->iconButton(),
            ]);
    }
}
