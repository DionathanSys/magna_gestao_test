<?php

namespace App\Filament\Resources\IncomingEmails\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Anexos';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('original_filename')->label('Arquivo')->wrap(),
                TextColumn::make('kind')->label('Tipo')->badge(),
                TextColumn::make('mime_type')->label('MIME')->toggleable(),
                TextColumn::make('size_bytes')->label('Tamanho')->numeric(),
                TextColumn::make('path')->label('Path')->wrap(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i'),
            ]);
    }
}
