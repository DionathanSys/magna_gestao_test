<?php

namespace App\Filament\Imports;

use App\Models\Viagem;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ViagemImporter extends Importer
{
    protected static ?string $model = Viagem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('veiculo_id')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('numero_viagem')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('numero_custo_frete')
                ->rules(['max:50']),
            ImportColumn::make('documento_transporte')
                ->rules(['max:50']),
            ImportColumn::make('tipo_viagem')
                ->rules(['max:255']),
            ImportColumn::make('valor_frete')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('valor_cte')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('valor_nfs')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('valor_icms')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('km_rodado')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('km_pago')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('km_divergencia')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('km_cadastro')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('km_rota_corrigido')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('km_pago_excedente')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('km_rodado_excedente')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('km_cobrar')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('motivo_divergencia')
                ->rules(['max:255']),
            ImportColumn::make('peso')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('entregas')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('data_competencia')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('data_inicio')
                ->requiredMapping()
                ->rules(['required', 'datetime']),
            ImportColumn::make('data_fim')
                ->requiredMapping()
                ->rules(['required', 'datetime']),
            ImportColumn::make('conferido')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('divergencias'),
            ImportColumn::make('created_by')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('updated_by')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('checked_by')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('km_dispersao')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('dispersao_percentual')
                ->numeric()
                ->rules(['integer']),
        ];
    }

    public function resolveRecord(): Viagem
    {
        return Viagem::firstOrNew([
            'numero_viagem' => $this->data['numero_viagem'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your viagem import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
