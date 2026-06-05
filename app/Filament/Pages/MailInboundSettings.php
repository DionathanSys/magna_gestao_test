<?php

namespace App\Filament\Pages;

use App\Enum\UnidadeNegocioEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Inerba\DbConfig\AbstractPageSettings;

class MailInboundSettings extends AbstractPageSettings
{
    protected static ?string $title = 'Entrada de E-mails';

    protected function settingName(): string
    {
        return 'config-mail-inbound';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    public function getDefaultData(): array
    {
        return [
            'enabled' => true,
            'allowed_senders' => [],
            'issuer_document' => null,
            'sale_recipient_document' => db_config('config-mail-inbound.bugio_recipient_cnpj'),
            'unidade_negocio' => null,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Captura e Correspondência')
                    ->columns(12)
                    ->columnSpan(8)
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Habilitado')
                            ->columnSpanFull()
                            ->default(true),
                        Repeater::make('allowed_senders')
                            ->label('Remetentes Permitidos')
                            ->columnSpanFull()
                            ->addActionLabel('Adicionar remetente')
                            ->simple(
                                TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->autocomplete(false)
                            ),
                        TextInput::make('issuer_document')
                            ->label('Documento do emissor das notas')
                            ->columnSpan(6)
                            ->required()
                            ->autocomplete(false),
                        TextInput::make('sale_recipient_document')
                            ->label('Documento do destinatario da nota de venda')
                            ->columnSpan(6)
                            ->required()
                            ->autocomplete(false),
                        Select::make('unidade_negocio')
                            ->label('Unidade de Negócio para criação automática')
                            ->options(UnidadeNegocioEnum::toSelectArray())
                            ->native(false)
                            ->columnSpan(6)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }
}
