<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Inerba\DbConfig\AbstractPageSettings;

class AlertasSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Alertas';

    protected string $view = 'filament.pages.alertas-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    protected function settingName(): string
    {
        return 'config-alertas';
    }

    public function getDefaultData(): array
    {
        return [
            'telegram' => [
                'ordem_servico_criada' => [
                    'ativo' => true,
                    'destinatarios' => [],
                ],
            ],
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Alertas via Telegram')
                    ->description('Configure os alertas enviados pelo bot do Telegram. O bot precisa estar habilitado no ambiente.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Nova ordem de serviço')
                            ->description('Envia os agendamentos pendentes e os planos preventivos do veículo da nova OS.')
                            ->columns(12)
                            ->columnSpanFull()
                            ->schema([
                                Toggle::make('telegram.ordem_servico_criada.ativo')
                                    ->label('Alerta ativo')
                                    ->default(true)
                                    ->columnSpan(3),
                                Select::make('telegram.ordem_servico_criada.destinatarios')
                                    ->label('Destinatários')
                                    ->options(fn (): array => User::query()
                                        ->whereNotNull('telegram_chat_id')
                                        ->where('telegram_chat_id', '!=', '')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (User $user): array => [
                                            $user->id => $user->name.' ('.$user->telegram_chat_id.')',
                                        ])
                                        ->all())
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->columnSpan(9)
                                    ->helperText('Somente usuários com Telegram Chat ID cadastrado aparecem nesta lista.'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
}
