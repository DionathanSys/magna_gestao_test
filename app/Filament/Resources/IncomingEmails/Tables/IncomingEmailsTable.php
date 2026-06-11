<?php

namespace App\Filament\Resources\IncomingEmails\Tables;

use App\Filament\Actions\ExportPdfBulkAction;
use App\Jobs\MailInbound\ProcessIncomingBugioCteReturnEmailJob;
use App\Models\IncomingEmail;
use App\Services\MailInbound\InboundMessageIngestionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
                TextColumn::make('id')->label('ID')->sortable()->toggleable(),
                TextColumn::make('from_email')->label('Remetente')->searchable()->toggleable(),
                TextColumn::make('subject')->label('Assunto')->searchable()->wrap()->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->toggleable(),
                TextColumn::make('attachments_count')->label('Anexos')->counts('attachments')->toggleable(),
                TextColumn::make('fiscalDocument.tipo_documento')->label('Tipo Doc.')->placeholder('-')->toggleable(),
                TextColumn::make('fiscalDocument.numero_nota')->label('Nota')->placeholder('-')->toggleable(),
                TextColumn::make('fiscalDocument.destinatario_documento')->label('Doc. Destino')->placeholder('-')->toggleable(),
                TextColumn::make('fiscalDocument.integrado.nome')->label('Integrado')->placeholder('-')->wrap()->toggleable(),
                TextColumn::make('tem_retorno_cte')
                    ->label('Retorno CTe')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Vinculado' : 'Nao mapeado')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
                TextColumn::make('pending_summary')->label('O que falta')->wrap()->toggleable(),
                TextColumn::make('received_at')->label('Recebido em')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportPdfBulkAction::make(
                        'exportar_pdf',
                        'Emails Capturados',
                        [
                            ['key' => 'id', 'label' => 'ID', 'align' => 'center', 'width' => '5%'],
                            ['key' => 'from_email', 'label' => 'Remetente', 'width' => '15%'],
                            ['key' => 'subject', 'label' => 'Assunto', 'width' => '22%'],
                            ['key' => 'status', 'label' => 'Status', 'align' => 'center', 'width' => '8%'],
                            ['key' => 'num_anexos', 'label' => 'Anexos', 'align' => 'center', 'width' => '6%'],
                            ['key' => 'retorno_cte', 'label' => 'Retorno CTe', 'align' => 'center', 'width' => '8%'],
                            ['key' => 'tipo_doc', 'label' => 'Tipo Doc.', 'align' => 'center', 'width' => '8%'],
                            ['key' => 'nota', 'label' => 'Nota', 'width' => '10%'],
                            ['key' => 'integrado', 'label' => 'Integrado', 'width' => '14%'],
                            ['key' => 'received_at', 'label' => 'Recebido em', 'align' => 'center', 'width' => '12%'],
                        ],
                        fn ($records) => $records->load(['attachments', 'fiscalDocument.integrado'])
                            ->map(fn ($r) => [
                                'id' => $r->id,
                                'from_email' => e($r->from_email ?? '-'),
                                'subject' => e(Str::limit($r->subject, 60) ?? '-'),
                                'status' => e($r->status ?? '-'),
                                'num_anexos' => $r->attachments->count(),
                                'retorno_cte' => $r->tem_retorno_cte ? 'Vinculado' : 'Nao mapeado',
                                'tipo_doc' => e($r->fiscalDocument?->tipo_documento ?? '-'),
                                'nota' => e($r->fiscalDocument?->numero_nota ?? '-'),
                                'integrado' => e($r->fiscalDocument?->integrado?->nome ?? '-'),
                                'received_at' => $r->received_at?->format('d/m/Y H:i') ?? '-',
                            ])->toArray(),
                    ),
                ]),
            ]);
    }
}
