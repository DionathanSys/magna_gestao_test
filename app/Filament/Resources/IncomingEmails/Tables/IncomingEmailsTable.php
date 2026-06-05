<?php

namespace App\Filament\Resources\IncomingEmails\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('received_at')->label('Recebido em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
            ]);
    }
}
