<?php

namespace App\Filament\Resources\Pneus;

use App\Filament\Resources\Pneus\Pages\CreatePneu;
use App\Filament\Resources\Pneus\Pages\EditPneu;
use App\Filament\Resources\Pneus\Pages\ListPneus;
use App\Filament\Resources\Pneus\Pages\ViewPneu;
use App\Filament\Resources\Pneus\RelationManagers\ConsertosRelationManager;
use App\Filament\Resources\Pneus\RelationManagers\HistoricoMovimentacaoRelationManager;
use App\Filament\Resources\Pneus\RelationManagers\RecapagensRelationManager;
use App\Filament\Resources\Pneus\Schemas\PneuForm;
use App\Filament\Resources\Pneus\Schemas\PneuInfolist;
use App\Filament\Resources\Pneus\Tables\PneusTable;
use App\Livewire\PneuResource as LivewirePneuResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Pneu;
use Filament\Resources\RelationManagers\RelationGroup;
use UnitEnum;

class PneuResource extends Resource
{
    protected static ?string $model = Pneu::class;

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static ?string $recordTitleAttribute = 'numero_fogo';

    public static function form(Schema $schema): Schema
    {
        return PneuForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PneuInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PneusTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make('Grupo de Relações', [
                HistoricoMovimentacaoRelationManager::class,
                RecapagensRelationManager::class,
                ConsertosRelationManager::class,
            ])
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPneus::route('/'),
            'view' => ViewPneu::route('/{record}'),
            'edit' => EditPneu::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getWidgets(): array
{
    return [
        LivewirePneuResource::class,
    ];
}
}
