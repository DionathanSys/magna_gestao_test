<?php

namespace App\Filament\Resources\IncomingEmails\RelationManagers;

use App\Models\IncomingEmailAttachment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
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
                    ->size(ActionSize::Small)
                    ->modalHeading(fn (IncomingEmailAttachment $record): string => "Anexo: {$record->original_filename}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(function (IncomingEmailAttachment $record): HtmlString {
                        $size = $record->size_bytes
                            ? number_format($record->size_bytes / 1024, 1).' KB'
                            : '-';

                        $html = "
<div class=\"space-y-4\">
    <div class=\"grid grid-cols-2 gap-4\">
        <div><strong>ID:</strong> {$record->id}</div>
        <div><strong>Nome:</strong> ".e($record->original_filename).'</div>
        <div><strong>Tipo:</strong> <span class="filament-badge">'.e($record->kind ?? '-').'</span></div>
        <div><strong>MIME:</strong> '.e($record->mime_type ?? '-')."</div>
        <div><strong>Tamanho:</strong> {$size}</div>
        <div><strong>Status:</strong> <span class=\"filament-badge\">".e($record->status ?? 'stored')."</span></div>
        <div><strong>Criado em:</strong> {$record->created_at?->format('d/m/Y H:i')}</div>
        <div><strong>Caminho:</strong> ".e($record->path).'</div>
    </div>';

                        if ($record->kind === 'xml') {
                            $content = Storage::disk($record->disk)->get($record->path);
                            $escaped = $content ? e($content) : 'Arquivo nao encontrado';
                            $html .= "
    <div>
        <h4 class=\"text-sm font-medium mb-2\">Conteudo do XML</h4>
        <pre class=\"bg-gray-100 dark:bg-gray-800 p-3 rounded text-xs overflow-auto max-h-96\">{$escaped}</pre>
    </div>";
                        }

                        if ($record->kind === 'pdf') {
                            $url = Storage::disk($record->disk)->url($record->path);
                            $src = $url ? e($url) : '';
                            $html .= $src ? "
    <div>
        <h4 class=\"text-sm font-medium mb-2\">Visualizacao do PDF</h4>
        <iframe src=\"{$src}\" class=\"w-full\" style=\"height: 80vh;\" frameborder=\"0\"></iframe>
    </div>" : '
    <div class="text-gray-400">Arquivo nao encontrado</div>';
                        }

                        $html .= '</div>';

                        return new HtmlString($html);
                    }),
            ]);
    }
}
