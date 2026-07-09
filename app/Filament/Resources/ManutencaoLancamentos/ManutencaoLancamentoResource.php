<?php

namespace App\Filament\Resources\ManutencaoLancamentos;

use App\Filament\Resources\ManutencaoLancamentos\Pages\ListManutencaoLancamentos;
use App\Filament\Resources\ManutencaoLancamentos\Tables\ManutencaoLancamentosTable;
use App\Models\ManutencaoLancamento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ManutencaoLancamentoResource extends Resource
{
    protected static ?string $model = ManutencaoLancamento::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $modelLabel = 'Lançamento de Manutenção';

    protected static ?string $pluralModelLabel = 'Lançamentos de Manutenção';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return ManutencaoLancamentosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManutencaoLancamentos::route('/'),
        ];
    }
}
