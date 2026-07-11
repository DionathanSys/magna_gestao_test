<?php

namespace App\Filament\Resources\Agendamentos;

use App\Filament\Resources\Agendamentos\Pages\CreateAgendamento;
use App\Filament\Resources\Agendamentos\Pages\EditAgendamento;
use App\Filament\Resources\Agendamentos\Pages\ListAgendamentos;
use App\Filament\Resources\Agendamentos\Pages\MobileOperacaoAgendamentos;
use App\Filament\Resources\Agendamentos\Pages\OperacaoAgendamentos;
use App\Filament\Resources\Agendamentos\Pages\ViewAgendamento;
use App\Filament\Resources\Agendamentos\Schemas\AgendamentoForm;
use App\Filament\Resources\Agendamentos\Schemas\AgendamentoInfolist;
use App\Filament\Resources\Agendamentos\Tables\AgendamentosTable;
use App\Models\Agendamento;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AgendamentoResource extends Resource
{
    protected static ?string $model = Agendamento::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $modelLabel = 'Agendamento';

    protected static ?string $pluralModelLabel = 'Agendamentos';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AgendamentoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AgendamentoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgendamentosTable::configure($table);
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
            'index' => ListAgendamentos::route('/'),
            'operacao' => OperacaoAgendamentos::route('/operacao'),
            'mobile-operacao' => MobileOperacaoAgendamentos::route('/mobile'),
            // 'create' => CreateAgendamento::route('/create'),
            // 'view' => ViewAgendamento::route('/{record}'),
            'edit' => EditAgendamento::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            ...parent::getNavigationItems(),
            NavigationItem::make('Operação Agendamentos')
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-queue-list')
                ->sort((static::getNavigationSort() ?? 0) + 1)
                ->url(static::getUrl('operacao'))
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.agendamentos.operacao')),
        ];
    }
}
