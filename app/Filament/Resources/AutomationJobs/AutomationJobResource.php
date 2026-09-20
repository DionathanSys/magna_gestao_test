<?php

namespace App\Filament\Resources\AutomationJobs;

use App\Filament\Resources\AutomationJobs\Pages\ListAutomationJobs;
use App\Filament\Resources\AutomationJobs\Pages\ViewAutomationJob;
use App\Filament\Resources\AutomationJobs\Schemas\AutomationJobForm;
use App\Filament\Resources\AutomationJobs\Schemas\AutomationJobInfolist;
use App\Filament\Resources\AutomationJobs\Tables\AutomationJobsTable;
use App\Models\AutomationJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AutomationJobResource extends Resource
{
    protected static ?string $model = AutomationJob::class;

    protected static string|UnitEnum|null $navigationGroup = 'Automações';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $modelLabel = 'Job de Automação';

    protected static ?string $pluralModelLabel = 'Jobs de Automação';

    public static function form(Schema $schema): Schema
    {
        return AutomationJobForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AutomationJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AutomationJobsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAutomationJobs::route('/'),
            'view' => ViewAutomationJob::route('/{record}'),
        ];
    }
}
