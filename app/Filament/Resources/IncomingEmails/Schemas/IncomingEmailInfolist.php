<?php

namespace App\Filament\Resources\IncomingEmails\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncomingEmailInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Email')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('provider')->label('Provider'),
                        TextEntry::make('from_email')->label('Remetente'),
                        TextEntry::make('from_name')->label('Nome Remetente')->placeholder('-'),
                        TextEntry::make('subject')->label('Assunto')->columnSpanFull()->placeholder('-'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('received_at')->label('Recebido em')->dateTime('d/m/Y H:i:s')->placeholder('-'),
                        TextEntry::make('error_message')->label('Erro')->columnSpanFull()->placeholder('-'),
                    ]),
                Section::make('Documento Fiscal')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('fiscalDocument.tipo_documento')->label('Tipo')->placeholder('-'),
                        TextEntry::make('fiscalDocument.numero_nota')->label('Numero Nota')->placeholder('-'),
                        TextEntry::make('fiscalDocument.chave_nfe')->label('Chave NFe')->columnSpanFull()->placeholder('-'),
                        TextEntry::make('fiscalDocument.emitente_documento')->label('Doc. Emissor')->placeholder('-'),
                        TextEntry::make('fiscalDocument.destinatario_documento')->label('Doc. Destinatario')->placeholder('-'),
                        TextEntry::make('fiscalDocument.transportador_documento')->label('Doc. Transportador')->placeholder('-'),
                        TextEntry::make('fiscalDocument.placa_transportador')->label('Placa')->placeholder('-'),
                        TextEntry::make('fiscalDocument.integrado.nome')->label('Integrado')->placeholder('-'),
                    ]),
                Section::make('Headers')
                    ->schema([
                        KeyValueEntry::make('raw_headers')->label('Headers'),
                    ]),
            ]);
    }
}
