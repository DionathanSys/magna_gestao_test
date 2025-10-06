<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\{Models, Services};
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class VisualizarComentarioAction
{
    public static function make(): Action
    {
        return Action::make('visualizar-comentarios')
            ->modalHeading('Comentários')
            ->slideOver()
            ->modalSubmitAction(false)
            ->schema([
                \Filament\Infolists\Components\RepeatableEntry::make('comentarios')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('conteudo')
                            ->label('Comentário')
                            ->html(),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),
                    ])
            ])->icon('heroicon-o-chat-bubble-left-ellipsis');
    }
}
