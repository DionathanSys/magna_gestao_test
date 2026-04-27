<?php

namespace App\Services;

class FreteCalculador
{
    /**
     * Calcula o piso mínimo de frete baseado em coeficientes tabelados.
     *
     * @param float $kmTotal Quilometragem total (ida + volta)
     * @param bool $temRetornoVazio Se a viagem tem retorno vazio
     * @return array Array com detalhes do cálculo
     */
    public static function calcularPisoMinimo(float $kmTotal, bool $temRetornoVazio = false): array
    {
        // Recupera coeficientes da configuração
        $ccd = (float) db_config('config-bugio.coeficiente-ccd', 0);
        $cc = (float) db_config('config-bugio.coeficiente-cc', 0);
        $percentualRetorno = (float) db_config('config-bugio.percentual-retorno-vazio', 0.92);

        // Se coeficientes não estão configurados, retorna array vazio/defaults
        if ($ccd <= 0 || $cc <= 0) {
            return [
                'km_ida' => 0,
                'ccd' => $ccd,
                'cc' => $cc,
                'valor_ida' => 0,
                'valor_retorno' => 0,
                'piso_minimo' => 0,
                'frete_antigo' => 0,
                'frete_final' => 0,
                'configurado' => false,
            ];
        }

        // Calcula km de ida (metade do total)
        $kmIda = $kmTotal / 2;

        // Calcula valor da ida: (distância × CCD) + CC
        $valorIda = ($kmIda * $ccd) + $cc;

        // Calcula valor do retorno vazio se aplicável: 0.92 × distância × CCD
        $valorRetorno = $temRetornoVazio ? ($percentualRetorno * $kmIda * $ccd) : 0;

        // Piso mínimo = ida + retorno
        $pisoMinimo = $valorIda + $valorRetorno;

        // Frete antigo (cálculo anterior)
        $valorQuilometro = (float) db_config('config-bugio.valor-quilometro', 0);
        $freteAntigo = $valorQuilometro * $kmTotal;

        // Frete final = máximo entre o antigo e o piso
        $freteFinal = max($freteAntigo, $pisoMinimo);

        return [
            'km_ida' => $kmIda,
            'ccd' => $ccd,
            'cc' => $cc,
            'valor_ida' => $valorIda,
            'valor_retorno' => $valorRetorno,
            'piso_minimo' => $pisoMinimo,
            'frete_antigo' => $freteAntigo,
            'frete_final' => $freteFinal,
            'configurado' => true,
        ];
    }

    /**
     * Formata um valor numérico para exibição de moeda.
     *
     * @param float $valor
     * @return string
     */
    public static function formatarMoeda(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Formata um valor numérico para distância em KM.
     *
     * @param float $valor
     * @return string
     */
    public static function formatarKm(float $valor): string
    {
        return number_format($valor, 2, ',', '.') . ' km';
    }
}
