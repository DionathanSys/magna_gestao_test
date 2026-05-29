<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ViagemNumberService
{
    public const GLOBAL_SCOPE = 'INTERNO';

    public const GLOBAL_PREFIX = 'VI';

    /**
     * Gera o próximo número de viagem e retorna o número formatado
     * e o número sequencial inteiro.
     *
     * Quando nenhum escopo é informado, usa a sequência global interna.
     *
     * Ex: escopo null -> "VI-0001"
     * Ex: escopo "BUGIO" -> "BUGIO-0001"
     *
     * @param string|null $scope
     * @return array ['numero_viagem' => string, 'numero_sequencial' => int]
     */
    public function next(?string $scope = null): array
    {
        $scope = strtoupper(trim($scope ?: self::GLOBAL_SCOPE));
        $prefix = $scope === self::GLOBAL_SCOPE ? self::GLOBAL_PREFIX : $scope;

        return DB::transaction(function () use ($scope, $prefix) {
            $row = DB::table('viagem_sequences')
                ->where('cliente', $scope)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $num = 1;
                $now = now();
                DB::table('viagem_sequences')->insert([
                    'cliente' => $scope,
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
