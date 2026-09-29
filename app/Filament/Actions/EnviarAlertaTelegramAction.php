<?php

namespace App\Filament\Actions;

use App\Jobs\SendTelegramAlertJob;
use App\Models\TelegramAlert;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class EnviarAlertaTelegramAction
{
    public static function make(?Model $alertable = null): Action
    {
        return Action::make('enviar_alerta_telegram')
            ->label('Enviar alerta Telegram')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->modalHeading('Enviar alerta pelo Telegram')
            ->modalDescription('Escolha os destinatários. O alerta será processado pela fila.')
            ->modalWidth('4xl')
            ->schema([
                Grid::make(12)
                    ->schema([
                        Section::make('Mensagem')
                            ->schema([
                                RichEditor::make('message_html')
                                    ->label('Mensagem')
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'link',
                                        'bulletList',
                                        'orderedList',
                                        'blockquote',
                                        'code',
                                        'h2',
                                        'h3',
                                        'redo',
                                        'undo',
                                    ])
                                    ->helperText('A formatação será convertida para HTML compatível com o Telegram.'),
                            ])
                            ->columnSpan(8),
                        Section::make('Destinatários')
                            ->schema([
                                Select::make('recipient_ids')
                                    ->label('Usuários')
                                    ->options(fn (): array => User::query()
                                        ->whereNotNull('telegram_chat_id')
                                        ->where('telegram_chat_id', '!=', '')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Somente usuários ativos com Chat ID configurado são exibidos.'),
                            ])
                            ->columnSpan(4),
                        Section::make('Opções do Telegram')
                            ->schema([
                                Toggle::make('disable_web_page_preview')
                                    ->label('Desativar preview de links')
                                    ->default(true),
                                Toggle::make('disable_notification')
                                    ->label('Enviar sem notificação sonora')
                                    ->default(false),
                                Toggle::make('protect_content')
                                    ->label('Proteger contra encaminhamento')
                                    ->default(false),
                                Repeater::make('buttons')
                                    ->label('Botões de ação')
                                    ->schema([
                                        TextInput::make('text')
                                            ->label('Texto')
                                            ->required()
                                            ->maxLength(64),
                                        TextInput::make('url')
                                            ->label('URL')
                                            ->url()
                                            ->required()
                                            ->maxLength(2048),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->addActionLabel('Adicionar botão')
                                    ->helperText('Os botões serão exibidos abaixo da mensagem.'),
                            ])
                            ->columnSpan(8),
                        Section::make('Arquivo')
                            ->schema([
                                FileUpload::make('file_path')
                                    ->label('Anexo opcional')
                                    ->disk('local')
                                    ->directory('telegram/alerts')
                                    ->visibility('private')
                                    ->maxSize(50 * 1024)
                                    ->helperText('O arquivo será enviado em uma segunda mensagem e removido após o envio para todos os destinatários.'),
                            ])
                            ->columnSpan(4),
                    ]),
            ])
            ->action(function (array $data, ?Model $record = null) use ($alertable): void {
                try {
                    $recipientIds = collect($data['recipient_ids'] ?? [])
                        ->map(fn (mixed $id): int => (int) $id)
                        ->unique()
                        ->values();

                    if ($recipientIds->isEmpty()) {
                        throw new \RuntimeException('Selecione pelo menos um destinatário.');
                    }

                    $users = User::query()
                        ->whereIn('id', $recipientIds->all())
                        ->whereNotNull('telegram_chat_id')
                        ->where('telegram_chat_id', '!=', '')
                        ->get();

                    if ($users->count() !== $recipientIds->count()) {
                        throw new \RuntimeException('Um ou mais destinatários não possuem Telegram configurado.');
                    }

                    $alert = DB::transaction(function () use ($data, $users, $record, $alertable): TelegramAlert {
                        $alert = new TelegramAlert([
                            'created_by' => Auth::id(),
                            'message_html' => $data['message_html'],
                            'telegram_options' => [
                                'disable_web_page_preview' => (bool) ($data['disable_web_page_preview'] ?? true),
                                'disable_notification' => (bool) ($data['disable_notification'] ?? false),
                                'protect_content' => (bool) ($data['protect_content'] ?? false),
                                'buttons' => $data['buttons'] ?? [],
                            ],
                            'file_path' => $data['file_path'] ?? null,
                            'file_disk' => 'local',
                            'status' => 'pending',
                        ]);

                        $alert->alertable()->associate($record ?? $alertable);
                        $alert->save();

                        $alert->recipients()->createMany(
                            $users->map(fn (User $user): array => [
                                'user_id' => $user->id,
                                'chat_id' => $user->telegram_chat_id,
                                'status' => 'pending',
                            ])->all(),
                        );

                        return $alert;
                    });

                    SendTelegramAlertJob::dispatch($alert->id)
                        ->onQueue((string) config('services.telegram.queue', 'integracoes'));

                    Notification::make()
                        ->success()
                        ->title('Alerta enfileirado')
                        ->body('O Telegram enviará o alerta para os destinatários selecionados.')
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('Alerta não enfileirado')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }
}
