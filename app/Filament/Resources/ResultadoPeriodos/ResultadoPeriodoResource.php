<?php

namespace App\Filament\Resources\ResultadoPeriodos;

use App\Filament\Resources\ResultadoPeriodos\Pages\CreateResultadoPeriodo;
use App\Filament\Resources\ResultadoPeriodos\Pages\EditResultadoPeriodo;
use App\Filament\Resources\ResultadoPeriodos\Pages\ListResultadoPeriodos;
use App\Filament\Resources\ResultadoPeriodos\Pages\ViewResultadoPeriodo;
use App\Filament\Resources\ResultadoPeriodos\RelationManagers\AbastecimentosRelationManager;
use App\Filament\Resources\ResultadoPeriodos\RelationManagers\DocumentosFreteRelationManager;
use App\Filament\Resources\ResultadoPeriodos\RelationManagers\ViagensRelationManager;
use App\Filament\Resources\ResultadoPeriodos\Schemas\ResultadoPeriodoForm;
use App\Filament\Resources\ResultadoPeriodos\Schemas\ResultadoPeriodoInfolist;
use App\Filament\Resources\ResultadoPeriodos\Tables\ResultadoPeriodosTable;
use App\Models\ResultadoPeriodo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResultadoPeriodoResource extends Resource
{
    protected static ?string $model = ResultadoPeriodo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ResultadoPeriodoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ResultadoPeriodoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResultadoPeriodosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AbastecimentosRelationManager::class,
            ViagensRelationManager::class,
            DocumentosFreteRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResultadoPeriodos::route('/'),
            // 'create' => CreateResultadoPeriodo::route('/create'),
            'view' => ViewResultadoPeriodo::route('/{record}'),
            'edit' => EditResultadoPeriodo::route('/{record}/edit'),
        ];
    }
}
