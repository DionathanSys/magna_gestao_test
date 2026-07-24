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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class IncomingEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['attachments', 'fiscalDocument.integrado', 'cteReturnMessages.request'])
                ->withExists(['cteReturnMessages as tem_retorno_cte']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(),
                TextColumn::make('external_id')->label('UID/Externo')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('message_id')->label('Message-ID')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('from_email')->label('Remetente')->searchable()->toggleable(),
                TextColumn::make('subject')->label('Assunto')->searchable()->wrap()->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->toggleable(),
                TextColumn::make('attachments_count')->label('Anexos')->counts('attachments')->toggleable(),
                TextColumn::make('captured_document_type')->label('Tipo Doc.')->placeholder('-')->toggleable(),
                TextColumn::make('captured_document_number')->label('Nota')->placeholder('-')->toggleable(),
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
                Filter::make('id')
                    ->label('ID')
                    ->schema([
                        TextInput::make('id')->label('ID')->numeric(),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['id'] ?? null) ? "ID: {$data['id']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['id'] ?? null),
                        fn (Builder $query): Builder => $query->whereKey($data['id']),
                    )),
                Filter::make('external_id')
                    ->label('UID/Externo')
                    ->schema([
                        TextInput::make('external_id')->label('UID/Externo'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['external_id'] ?? null) ? "UID/Externo: {$data['external_id']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['external_id'] ?? null),
                        fn (Builder $query): Builder => $query->where('external_id', 'like', "%{$data['external_id']}%"),
                    )),
                Filter::make('message_id')
                    ->label('Message-ID')
                    ->schema([
                        TextInput::make('message_id')->label('Message-ID'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['message_id'] ?? null) ? "Message-ID: {$data['message_id']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['message_id'] ?? null),
                        fn (Builder $query): Builder => $query->where('message_id', 'like', "%{$data['message_id']}%"),
                    )),
                Filter::make('from_email')
                    ->label('Remetente')
                    ->schema([
                        TextInput::make('from_email')->label('Remetente'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['from_email'] ?? null) ? "Remetente: {$data['from_email']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['from_email'] ?? null),
                        fn (Builder $query): Builder => $query->where('from_email', 'like', "%{$data['from_email']}%"),
                    )),
                Filter::make('subject')
                    ->label('Assunto')
                    ->schema([
                        TextInput::make('subject')->label('Assunto'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['subject'] ?? null) ? "Assunto: {$data['subject']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['subject'] ?? null),
                        fn (Builder $query): Builder => $query->where('subject', 'like', "%{$data['subject']}%"),
                    )),
                SelectFilter::make('fiscal_document_type')
                    ->label('Tipo Doc.')
                    ->options([
                        'sale' => 'Venda',
                        'remittance' => 'Remessa',
                        'unknown' => 'Fora da regra',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas('fiscalDocument', fn (Builder $query): Builder => $query->where('tipo_documento', $data['value'])),
                    )),
                Filter::make('numero_nota')
                    ->label('Numero da nota')
                    ->schema([
                        TextInput::make('numero_nota')->label('Numero da nota'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['numero_nota'] ?? null) ? "Nota: {$data['numero_nota']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['numero_nota'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas('fiscalDocument', fn (Builder $query): Builder => $query->where('numero_nota', 'like', "%{$data['numero_nota']}%")),
                    )),
                Filter::make('destinatario_documento')
                    ->label('Doc. destino')
                    ->schema([
                        TextInput::make('destinatario_documento')->label('Doc. destino'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['destinatario_documento'] ?? null) ? "Doc. destino: {$data['destinatario_documento']}" : null)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['destinatario_documento'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas('fiscalDocument', fn (Builder $query): Builder => $query->where('destinatario_documento', 'like', "%{$data['destinatario_documento']}%")),
                    )),
                SelectFilter::make('integrado_id')
                    ->label('Integrado')
                    ->relationship('fiscalDocument.integrado', 'nome')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('tem_documento_fiscal')
                    ->label('Documento fiscal gerado?')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Com documento')
                    ->falseLabel('Sem documento')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('fiscalDocument'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('fiscalDocument'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('tem_anexos')
                    ->label('Possui anexos?')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Com anexos')
                    ->falseLabel('Sem anexos')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('attachments'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('attachments'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('tem_retorno_cte')
                    ->label('Retorno CTe vinculado?')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Vinculado')
                    ->falseLabel('Nao vinculado')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('cteReturnMessages'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('cteReturnMessages'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
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
                Filter::make('received_at')
                    ->label('Recebido em')
                    ->schema([
                        DatePicker::make('received_from')->label('De'),
                        DatePicker::make('received_until')->label('Ate'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['received_from'] ?? null)) {
                            $indicators[] = 'Recebido de: '.$data['received_from'];
                        }

                        if (filled($data['received_until'] ?? null)) {
                            $indicators[] = 'Recebido ate: '.$data['received_until'];
                        }

                        return $indicators;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['received_from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('received_at', '>=', $date))
                        ->when($data['received_until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('received_at', '<=', $date))),
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
                        fn ($records) => $records->load(['attachments', 'fiscalDocument.integrado', 'cteReturnMessages.request'])
                            ->map(fn ($r) => [
                                'id' => $r->id,
                                'from_email' => e($r->from_email ?? '-'),
                                'subject' => e(Str::limit($r->subject, 60) ?? '-'),
                                'status' => e($r->status ?? '-'),
                                'num_anexos' => $r->attachments->count(),
                                'retorno_cte' => $r->tem_retorno_cte ? 'Vinculado' : 'Nao mapeado',
                                'tipo_doc' => e($r->captured_document_type ?? '-'),
                                'nota' => e($r->captured_document_number ?? '-'),
                                'integrado' => e($r->fiscalDocument?->integrado?->nome ?? '-'),
                                'received_at' => $r->received_at?->format('d/m/Y H:i') ?? '-',
                            ])->toArray(),
                    ),
                ]),
            ]);
    }
}
