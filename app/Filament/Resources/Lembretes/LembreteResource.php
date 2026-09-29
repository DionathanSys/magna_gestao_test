<?php

namespace App\Filament\Resources\Lembretes;

use App\Enum\Lembrete\StatusLembreteEnum;
use App\Enum\Lembrete\TipoLembreteEnum;
use App\Filament\Resources\Lembretes\Pages\CreateLembrete;
use App\Filament\Resources\Lembretes\Pages\EditLembrete;
use App\Filament\Resources\Lembretes\Pages\ListLembretes;
use App\Models\Lembrete;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LembreteResource extends Resource
{
    protected static ?string $model = Lembrete::class;

    protected static string|UnitEnum|null $navigationGroup = 'Automações';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $modelLabel = 'Alerta/Lembrete';

    protected static ?string $pluralModelLabel = 'Alertas e Lembretes';

    protected static ?string $recordTitleAttribute = 'titulo';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo')
                    ->label('Tipo')
                    ->options(collect(TipoLembreteEnum::cases())->mapWithKeys(
                        fn (TipoLembreteEnum $tipo): array => [$tipo->value => $tipo->label()]
                    )->all())
                    ->default(TipoLembreteEnum::LEMBRETE->value)
                    ->native(false)
                    ->required(),
                TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),
                Select::make('user_id')
                    ->label('Destinatário')
                    ->relationship(
                        'user',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->whereNotNull('telegram_chat_id'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (User $user): string => $user->name.' ('.$user->telegram_chat_id.')')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Somente usuários com Telegram Chat ID configurado aparecem aqui.'),
                DateTimePicker::make('scheduled_at')
                    ->label('Enviar em')
                    ->seconds(false)
                    ->required(),
                Textarea::make('mensagem')
                    ->label('Mensagem')
                    ->required()
                    ->maxLength(3900)
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText('O Telegram limita cada mensagem a 4096 caracteres.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state): string => $state instanceof TipoLembreteEnum ? $state->label() : (string) $state)
                    ->badge(),
                TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Destinatário')
                    ->searchable(),
                TextColumn::make('scheduled_at')
                    ->label('Programado para')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state): string => $state instanceof StatusLembreteEnum ? $state->label() : (string) $state)
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof StatusLembreteEnum ? $state->value : $state) {
                        'enviado' => 'success',
                        'falhou' => 'danger',
                        'enviando' => 'info',
                        'cancelado' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('sent_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', direction: 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'enviando' => 'Enviando',
                        'enviado' => 'Enviado',
                        'falhou' => 'Falhou',
                        'cancelado' => 'Cancelado',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLembretes::route('/'),
            'create' => CreateLembrete::route('/create'),
            'edit' => EditLembrete::route('/{record}/edit'),
        ];
    }
}
