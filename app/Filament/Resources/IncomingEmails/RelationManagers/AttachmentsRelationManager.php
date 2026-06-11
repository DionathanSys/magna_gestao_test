<?php

namespace App\Filament\Resources\IncomingEmails\RelationManagers;

use App\Models\IncomingEmailAttachment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Anexos';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->toggleable()->sortable(),
                TextColumn::make('original_filename')->label('Arquivo')->wrap()->toggleable()->searchable(),
                TextColumn::make('kind')->label('Tipo')->badge()->toggleable(),
                TextColumn::make('mime_type')->label('MIME')->toggleable(),
                TextColumn::make('size_bytes')->label('Tamanho')->numeric()->toggleable(),
                TextColumn::make('path')->label('Path')->wrap()->toggleable(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->toggleable(),
            ])
            ->recordActions([
                Action::make('visualizar')
                    ->label('Visualizar')
                    ->icon('heroicon-o-eye')
                    ->size(Size::Ex)
                    ->visible(fn (IncomingEmailAttachment $record): bool => $record->kind === 'pdf')
                    ->modalHeading(fn (IncomingEmailAttachment $record): string => "PDF: {$record->original_filename}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalContent(function (IncomingEmailAttachment $record): HtmlString {
                        $url = route('attachments.view', ['attachment' => $record->id]);

                        return new HtmlString("
<div class=\"-m-6\">
    <iframe src=\"{$url}\" class=\"w-full\" style=\"height: 85vh; display: block;\" frameborder=\"0\"></iframe>
</div>");
                    }),
            ]);
    }
}
