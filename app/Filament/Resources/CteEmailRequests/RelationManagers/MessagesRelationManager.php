<?php

namespace App\Filament\Resources\CteEmailRequests\RelationManagers;

use App\Filament\Resources\IncomingEmails\IncomingEmailResource;
use App\Models\CteEmailRequestMessage;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Mensagens';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('incomingEmail.attachments'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('direction')->label('Direcao')->badge()
                    ->color(fn (string $state): string => $state === 'outbound' ? 'info' : 'success'),
                TextColumn::make('from_email')->label('De/Remetente')->wrap(),
                TextColumn::make('subject')->label('Assunto')->wrap()->limit(60),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('matched_by')->label('Match')->placeholder('-'),
                TextColumn::make('incoming_email_id')->label('Email ID')->placeholder('-'),
                TextColumn::make('incomingEmail.attachments_count')
                    ->label('Anexos')
                    ->state(fn (CteEmailRequestMessage $record): int => $record->incomingEmail?->attachments->count() ?? 0),
                TextColumn::make('attachment_details')
                    ->label('Arquivos')
                    ->state(fn (CteEmailRequestMessage $record): array => $record->incomingEmail?->attachments
                        ->map(fn ($attachment): string => "{$attachment->original_filename} ({$attachment->kind}: {$attachment->status})")
                        ->all() ?? [])
                    ->listWithLineBreaks()
                    ->wrap(),
                TextColumn::make('incomingEmail.received_at')->label('Recebido em')->dateTime('d/m/Y H:i')->placeholder('-'),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i'),
                TextColumn::make('processed_at')->label('Processado em')->dateTime('d/m/Y H:i')->placeholder('-'),
            ])
            ->recordActions([
                Action::make('abrir_email')
                    ->label('Abrir email e anexos')
                    ->icon('heroicon-o-envelope-open')
                    ->url(fn (CteEmailRequestMessage $record): ?string => $record->incoming_email_id
                        ? IncomingEmailResource::getUrl('view', ['record' => $record->incoming_email_id])
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (CteEmailRequestMessage $record): bool => $record->incoming_email_id !== null),
            ]);
    }
}
