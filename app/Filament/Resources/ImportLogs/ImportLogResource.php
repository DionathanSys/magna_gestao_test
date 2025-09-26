<?php

namespace App\Filament\Resources\ImportLogs;

use App\Filament\Resources\ImportLogs\Pages\CreateImportLog;
use App\Filament\Resources\ImportLogs\Pages\EditImportLog;
use App\Filament\Resources\ImportLogs\Pages\ListImportLogs;
use App\Filament\Resources\ImportLogs\Pages\ViewImportLog;
use App\Filament\Resources\ImportLogs\Schemas\ImportLogForm;
use App\Filament\Resources\ImportLogs\Schemas\ImportLogInfolist;
use App\Filament\Resources\ImportLogs\Tables\ImportLogsTable;
use App\Models\ImportLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImportLogResource extends Resource
{
    protected static ?string $model = ImportLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'import_description';

    public static function form(Schema $schema): Schema
    {
        return ImportLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImportLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportLogs::route('/'),
            'create' => CreateImportLog::route('/create'),
            'view' => ViewImportLog::route('/{record}'),
            'edit' => EditImportLog::route('/{record}/edit'),
        ];
    }
}
