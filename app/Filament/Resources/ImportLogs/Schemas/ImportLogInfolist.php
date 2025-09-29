<?php

namespace App\Filament\Resources\ImportLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ImportLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('file_name'),
                TextEntry::make('file_path'),
                TextEntry::make('file_size'),
                TextEntry::make('file_hash'),
                TextEntry::make('import_type'),
                TextEntry::make('import_description'),
                TextEntry::make('user.name'),
                TextEntry::make('status'),
                TextEntry::make('total_rows')
                    ->numeric(),
                TextEntry::make('processed_rows')
                    ->numeric(),
                TextEntry::make('success_rows')
                    ->numeric(),
                TextEntry::make('error_rows')
                    ->numeric(),
                TextEntry::make('warning_rows')
                    ->numeric(),
                    TextEntry::make('errors')
                    ->label('Erros de Importação')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'Nenhum erro encontrado';
                        }

                        // Verificar se já é um array ou se precisa decodificar
                        if (is_array($state)) {
                            $errors = $state;
                        } elseif (is_string($state)) {
                            $errors = json_decode($state, true) ?? [];
                        } else {
                            $errors = [];
                        }

                        if (empty($errors)) {
                            return 'Nenhum erro encontrado';
                        }

                        return implode("\n", array_map(function ($error, $index) {
                            return ($index + 1) . ". " . $error;
                        }, $errors, array_keys($errors)));
                    })
                    ->columnSpanFull(),
                TextEntry::make('skipped_rows')
                    ->numeric(),
                TextEntry::make('total_batches')
                    ->numeric(),
                TextEntry::make('processed_batches')
                    ->numeric(),
                TextEntry::make('progress_percentage')
                    ->numeric(),
                TextEntry::make('started_at')
                    ->dateTime(),
                TextEntry::make('finished_at')
                    ->dateTime(),
                TextEntry::make('duration_seconds')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
