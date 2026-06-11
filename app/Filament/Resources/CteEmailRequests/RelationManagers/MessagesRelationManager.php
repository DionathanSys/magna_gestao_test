<?php

namespace App\Filament\Resources\CteEmailRequests\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Mensagens';

    public function table(Table $table): Table
    {
        return $table
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
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i'),
                TextColumn::make('processed_at')->label('Processado em')->dateTime('d/m/Y H:i')->placeholder('-'),
            ]);
    }
}
