<?php

namespace App\Filament\Resources\Checklists;

use App\Filament\Resources\Checklists\Pages\CreateChecklist;
use App\Filament\Resources\Checklists\Pages\EditChecklist;
use App\Filament\Resources\Checklists\Pages\ListChecklists;
use App\Filament\Resources\Checklists\Pages\ViewChecklist;
use App\Filament\Resources\Checklists\Schemas\ChecklistForm;
use App\Filament\Resources\Checklists\Schemas\ChecklistInfolist;
use App\Filament\Resources\Checklists\Tables\ChecklistsTable;
use App\Models\Checklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChecklistResource extends Resource
{
    protected static ?string $model = Checklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ChecklistForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChecklistInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistsTable::configure($table);
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
            'index' => ListChecklists::route('/'),
            'create' => CreateChecklist::route('/create'),
            'view' => ViewChecklist::route('/{record}'),
            'edit' => EditChecklist::route('/{record}/edit'),
        ];
    }
}
