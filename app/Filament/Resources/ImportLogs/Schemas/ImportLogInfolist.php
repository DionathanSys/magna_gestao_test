<?php

namespace App\Filament\Resources\ImportLogs\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
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
                // TextEntry::make('errors')
                // ->label('Erros de Importação')
                // ->formatStateUsing(function ($state) {
                //     if (empty($state)) {
                //         return 'Nenhum erro encontrado';
                //     }

                //     // Verificar se já é um array ou se precisa decodificar
                //     if (is_array($state)) {
                //         $errors = $state;
                //     } elseif (is_string($state)) {
                //         $errors = json_decode($state, true) ?? [];
                //     } else {
                //         $errors = [];
                //     }

                //     if (empty($errors)) {
                //         return 'Nenhum erro encontrado';
                //     }

                //     return implode("\n", array_map(function ($error, $index) {
                //         return ($index + 1) . ". " . $error;
                //     }, $errors, array_keys($errors)));
                // })
                // ->columnSpanFull(),
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
                // RepeatableEntry::make('errors')
                //     ->label('Linhas com Erro')
                //     ->schema([
                //         TextEntry::make('message')
                //             ->label('Mensagem de Erro'),
                //     ])
                //     ->state(function ($record) {
                //         $errorRows = $record->errors;

                //         if (is_string($errorRows)) {
                //             $decoded = json_decode($errorRows, true);
                //             ds($errorRows)->label('Error Rows Raw');
                //             ds($decoded)->label('Error Rows Decoded');
                //             return is_array($decoded) ? $decoded : [];
                //         }

                //         return ds(is_array($errorRows) ? $errorRows : []);
                //     })
                //     ->columnSpanFull(),
                TextEntry::make('errors')
                    ->label('Erros de Importação')
                    ->formatStateUsing(function ($state) {
                        $normalizeError = function ($value) use (&$normalizeError): array {
                            if (is_array($value)) {
                                $normalized = [];

                                foreach ($value as $item) {
                                    foreach ($normalizeError($item) as $normalizedItem) {
                                        if ($normalizedItem !== '') {
                                            $normalized[] = $normalizedItem;
                                        }
                                    }
                                }

                                return $normalized;
                            }

                            if (is_scalar($value) || $value === null) {
                                return [trim((string) $value)];
                            }

                            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                            return [is_string($encoded) ? $encoded : 'Erro não legível'];
                        };

                        if (empty($state)) {
                            return '<span class="text-gray-500 italic">Nenhum erro encontrado</span>';
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
                            return '<span class="text-gray-500 italic">Nenhum erro encontrado</span>';
                        }

                        $normalizedErrors = [];

                        foreach ($errors as $error) {
                            foreach ($normalizeError($error) as $normalizedError) {
                                if ($normalizedError !== '') {
                                    $normalizedErrors[] = $normalizedError;
                                }
                            }
                        }

                        if ($normalizedErrors === []) {
                            return '<span class="text-gray-500 italic">Nenhum erro encontrado</span>';
                        }

                        // Agrupar erros similares
                        $groupedErrors = array_count_values($normalizedErrors);

                        // Formatar com HTML
                        $html = '<div class="space-y-2">';
                        foreach ($groupedErrors as $error => $count) {
                            $html .= '<div class="flex items-start gap-2">';
                            $html .= '<span class="text-red-500 mt-0.5">•</span>';
                            $html .= '<div class="flex-1">';
                            $html .= '<span class="text-sm">'.htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8').'</span>';
                            if ($count > 1) {
                                $html .= ' <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-red-700 bg-red-100 rounded-full ml-2">'.$count.'x</span>';
                            }
                            $html .= '</div>';
                            $html .= '</div>';
                        }
                        $html .= '</div>';

                        return $html;
                    })
                    ->html()
                    ->columnSpanFull(),
            ]);
    }
}
