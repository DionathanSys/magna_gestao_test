<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ViagemNumberService
{
    /**
     * Gera o próximo número de viagem para o cliente (prefixo) e retorna o número formatado
     * e o número sequencial inteiro.
     *
     * Ex: prefixo "BUGIO" -> "BUGIO-0001"
     *
     * @param string $cliente
     * @return array ['numero_viagem' => string, 'numero_sequencial' => int]
     */
    public function next(string $cliente): array
    {
        $prefix = strtoupper(trim($cliente));

        return DB::transaction(function () use ($prefix) {
            $row = DB::table('viagem_sequences')
                ->where('cliente', $prefix)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $num = 1;
                $now = now();
                DB::table('viagem_sequences')->insert([
                    'cliente' => $prefix,
                    'last_number' => $num,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $num = $row->last_number + 1;
                DB::table('viagem_sequences')
                    ->where('id', $row->id)
                    ->update(['last_number' => $num, 'updated_at' => now()]);
            }

            $formatted = sprintf('%s-%s', $prefix, str_pad($num, 4, '0', STR_PAD_LEFT));

            return ['numero_viagem' => $formatted, 'numero_sequencial' => (int) $num];
        });
    }
}
