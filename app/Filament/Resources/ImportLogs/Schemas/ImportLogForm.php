<?php

namespace App\Filament\Resources\ImportLogs\Schemas;

use App\Enum\Import\StatusImportacaoEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ImportLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('file_name')
                    ->required(),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('file_size'),
                TextInput::make('file_hash'),
                TextInput::make('import_type')
                    ->required(),
                TextInput::make('import_description'),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('status')
                    ->options(StatusImportacaoEnum::class)
                    ->required(),
                TextInput::make('total_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('processed_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('success_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('error_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('warning_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('skipped_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_batches')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('processed_batches')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('progress_percentage')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('errors'),
                TextInput::make('warnings'),
                TextInput::make('skipped_reasons'),
                TextInput::make('options'),
                TextInput::make('mapping'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('finished_at'),
                TextInput::make('duration_seconds')
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('summary'),
            ]);
    }
}
