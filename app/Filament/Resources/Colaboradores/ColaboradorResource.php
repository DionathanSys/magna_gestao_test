<?php

namespace App\Filament\Resources\Colaboradores;

use App\Filament\Resources\Colaboradores\Pages\ManageColaboradores;
use App\Models\Colaborador;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ColaboradorResource extends Resource
{
    protected static ?string $model = Colaborador::class;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastros';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Colaborador';

    protected static ?string $pluralModelLabel = 'Colaboradores';

    protected static ?string $slug = 'colaboradores';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo')
                ->label('Código')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(255),
            Select::make('tipo')
                ->label('Tipo')
                ->options([
                    'MECANICO' => 'Mecânico',
                    'MOTORISTA' => 'Motorista',
                    'OUTRO' => 'Outro',
                ])
                ->default('MECANICO')
                ->required(),
            Toggle::make('ativo')
                ->label('Ativo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->options([
                        'MECANICO' => 'Mecânico',
                        'MOTORISTA' => 'Motorista',
                        'OUTRO' => 'Outro',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => Auth::user()->is_admin),
                DeleteAction::make()
                    ->visible(fn (): bool => Auth::user()->is_admin),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->is_admin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageColaboradores::route('/'),
        ];
    }
}
