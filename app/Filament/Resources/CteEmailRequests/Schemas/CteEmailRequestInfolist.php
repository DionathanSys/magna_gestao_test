<?php

namespace App\Filament\Resources\CteEmailRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CteEmailRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Solicitação')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('documento_transporte')->label('Doc. Transporte'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('tipo_documento_solicitado')->label('Tipo'),
                        TextEntry::make('viagem.numero_viagem')->label('Viagem')->placeholder('-'),
                        TextEntry::make('viagem.veiculo.placa')->label('Placa')->placeholder('-'),
                        TextEntry::make('integrado.nome')->label('Integrado')->placeholder('-'),
                        TextEntry::make('created_by')->label('Criado por')->placeholder('-'),
                    ]),
                Section::make('Disparo')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('requested_at')->label('Solicitado em')->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('sent_at')->label('Enviado em')->dateTime('d/m/Y H:i:s')->placeholder('-'),
                        TextEntry::make('sent_subject')->label('Assunto enviado')->columnSpanFull(),
                        TextEntry::make('sent_to')->label('Para')->placeholder('-'),
                        TextEntry::make('sent_reply_to')->label('Reply-To')->placeholder('-'),
                        TextEntry::make('sent_cc')->label('CC')->placeholder('-'),
                        TextEntry::make('error_message')->label('Erro')->columnSpanFull()->placeholder('-'),
                    ]),
                Section::make('Resposta')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('last_response_at')->label('Ultima resposta em')->dateTime('d/m/Y H:i:s')->placeholder('-'),
                        TextEntry::make('completed_at')->label('Concluido em')->dateTime('d/m/Y H:i:s')->placeholder('-'),
                    ]),
                Section::make('Payload')
                    ->schema([
                        TextEntry::make('payload')
                            ->label('Dados da solicitacao')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-')
                            ->columnSpanFull()
                            ->prose()
                            ->copyable(),
                    ]),
            ]);
    }
}
