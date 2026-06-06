<?php

namespace App\Filament\Resources\ReceivedFiscalDocuments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReceivedFiscalDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Documento')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('tipo_documento')->label('Tipo')->badge(),
                        TextEntry::make('numero_nota')->label('Numero Nota'),
                        TextEntry::make('chave_nfe')->label('Chave')->columnSpanFull(),
                        TextEntry::make('emitente_nome')->label('Emitente')->placeholder('-'),
                        TextEntry::make('emitente_documento')->label('Doc. Emissor')->placeholder('-'),
                        TextEntry::make('destinatario_nome')->label('Destinatario')->placeholder('-'),
                        TextEntry::make('destinatario_documento')->label('Doc. Destinatario')->placeholder('-'),
                        TextEntry::make('transportador_documento')->label('Doc. Transportador')->placeholder('-'),
                        TextEntry::make('placa_transportador')->label('Placa')->placeholder('-'),
                        TextEntry::make('referenced_nfe_key')->label('Chave Referenciada')->placeholder('-'),
                        TextEntry::make('referenced_sale_number')->label('Numero Venda Referenciado')->placeholder('-'),
                        TextEntry::make('integrado.nome')->label('Integrado')->placeholder('-'),
                        TextEntry::make('pending_summary')->label('O que falta')->columnSpanFull(),
                    ]),
            ]);
    }
}
