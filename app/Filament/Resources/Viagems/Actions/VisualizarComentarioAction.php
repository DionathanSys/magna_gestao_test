<?php

namespace App\Filament\Resources\Viagems\Actions;

use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;

class VisualizarComentarioAction
{
    public static function make(): Action
    {
        return Action::make('visualizar-comentarios')
            ->modalHeading('Comentários')
            ->slideOver()
            ->modalSubmitAction(false)
            ->schema([
                RepeatableEntry::make('comentarios')
                    ->table([
                        TableColumn::make('Conteúdo')
                            ->wrapHeader(),
                        TableColumn::make('Criado Em'),
                        TableColumn::make('Criado Por'),
                    ])
                    ->schema([
                        TextEntry::make('conteudo')
                            ->label('Comentário')
                            ->html(),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('creator.name')
                            ->label('Criado por'),
                    ]),
                RepeatableEntry::make('cargas.integrado.comentarios')
                    ->table([
                        TableColumn::make('Conteúdo')
                            ->wrapHeader(),
                        TableColumn::make('Criado Em'),
                        TableColumn::make('Criado Por'),
                    ])
                    ->schema([
                        TextEntry::make('conteudo')
                            ->label('Comentário')
                            ->html(),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('creator.name')
                            ->label('Criado por'),
                    ]),
            ])->icon('heroicon-o-chat-bubble-left-ellipsis');
    }
}
