<?php

namespace App\Filament\Resources\ShipmentDocumentGroups\Schemas;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ShipmentDocumentGroupInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Grupo')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('pending_summary')->label('O que falta'),
                        TextEntry::make('sale_number')->label('Nota Venda')->placeholder('-'),
                        TextEntry::make('remittance_number')->label('Nota Remessa')->placeholder('-'),
                        TextEntry::make('integrado.nome')->label('Integrado')->placeholder('-'),
                        TextEntry::make('viagem.id')->label('Viagem')->placeholder('-'),
                        TextEntry::make('matched_at')->label('Pareado em')->dateTime('d/m/Y H:i:s')->placeholder('-'),
                    ]),
                Section::make('Documentos')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('saleDocument.numero_nota')->label('Numero Nota Venda')->placeholder('-'),
                        TextEntry::make('saleDocument.chave_nfe')->label('Chave Venda')->placeholder('-'),
                        TextEntry::make('remittanceDocument.numero_nota')->label('Numero Nota Remessa')->placeholder('-'),
                        TextEntry::make('remittanceDocument.chave_nfe')->label('Chave Remessa')->placeholder('-'),
                    ]),
            ]);
    }
}
