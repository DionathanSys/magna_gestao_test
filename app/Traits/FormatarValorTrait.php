<?php

namespace App\Traits;

trait FormatarValorTrait
{
    /**
     * Limpar e normalizar valor monetário
     * 
     * Converte diversos formatos para o padrão numérico aceito pelo Laravel
     * 
     * @param string|null $valor
     * @return string
     * 
     * @example
     * "1,800.00" → "1800.00"
     * "1.800,00" → "1800.00"
     * "R$ 200,00" → "200.00"
     * "200.00" → "200.00"
     * "200,00" → "200.00"
     * null → "0"
     */
    protected function limparValorMonetario(?string $valor): string
    {
        if (empty($valor)) {
            return '0';
        }

        // Remove espaços e símbolos de moeda
        $valor = trim($valor);
        $valor = preg_replace('/[R$\s]/', '', $valor);
        
        // Detectar formato do valor
        $temVirgula = strpos($valor, ',') !== false;
        $temPonto = strpos($valor, '.') !== false;
        
        if ($temVirgula && $temPonto) {
            // Tem ambos os separadores - determinar qual é decimal
            $posVirgula = strrpos($valor, ',');
            $posPonto = strrpos($valor, '.');
            
            if ($posVirgula > $posPonto) {
                // Formato brasileiro: "1.800,00"
                $valor = str_replace('.', '', $valor);  // Remove separador de milhar
                $valor = str_replace(',', '.', $valor); // Converte decimal
            } else {
                // Formato americano: "1,800.00"
                $valor = str_replace(',', '', $valor);  // Remove separador de milhar
            }
        } elseif ($temVirgula) {
            // Só tem vírgula - pode ser milhar ou decimal
            $qtdVirgulas = substr_count($valor, ',');
            
            if ($qtdVirgulas > 1) {
                // Múltiplas vírgulas = separador de milhar
                $valor = str_replace(',', '', $valor);
            } else {
                // Uma vírgula - verificar se é decimal ou milhar
                $partes = explode(',', $valor);
                
                // Se a parte após vírgula tem 2 dígitos, é decimal
                // Se tem 3 dígitos, é milhar
                if (strlen($partes[1]) === 2) {
                    // Formato brasileiro: "200,00"
                    $valor = str_replace(',', '.', $valor);
                } else {
                    // Formato milhar: "1,000"
                    $valor = str_replace(',', '', $valor);
                }
            }
        } elseif ($temPonto) {
            // Só tem ponto - pode ser milhar ou decimal
            $qtdPontos = substr_count($valor, '.');
            
            if ($qtdPontos > 1) {
                // Múltiplos pontos = separador de milhar brasileiro
                $valor = str_replace('.', '', $valor);
            } else {
                // Um ponto - verificar se é decimal ou milhar
                $partes = explode('.', $valor);
                
                // Se a parte após ponto tem 2 dígitos, é decimal
                // Se tem 3 dígitos, é milhar
                if (strlen($partes[1]) !== 2) {
                    // Formato milhar: "1.000"
                    $valor = str_replace('.', '', $valor);
                }
                // Senão, já está no formato correto: "200.00"
            }
        }
        
        // Remove qualquer caractere que não seja dígito, ponto ou sinal negativo
        $valor = preg_replace('/[^\d.-]/', '', $valor);
        
        // Garantir que tem pelo menos um valor válido
        if (empty($valor) || $valor === '-') {
            return '0';
        }
        
        return $valor;
    }

    /**
     * Converter valor monetário para float
     * 
     * @param string|null $valor
     * @return float
     */
    protected function converterParaFloat(?string $valor): float
    {
        return (float) $this->limparValorMonetario($valor);
    }

    /**
     * Normalizar múltiplos valores monetários em um array
     * 
     * @param array $data
     * @param array $campos
     * @return void
     */
    protected function normalizarValoresMonetarios(array &$data, array $campos): void
    {
        foreach ($campos as $campo) {
            if (isset($data[$campo])) {
                $data[$campo] = $this->limparValorMonetario($data[$campo]);
            }
        }
    }

    /**
     * Formatar valor para exibição em formato brasileiro
     * 
     * @param float|string|null $valor
     * @param int $decimais
     * @return string
     */
    protected function formatarValorBrasileiro($valor, int $decimais = 2): string
    {
        if (is_null($valor)) {
            return 'R$ 0,00';
        }

        $valor = (float) $valor;
        return 'R$ ' . number_format($valor, $decimais, ',', '.');
    }

    /**
     * Formatar valor para exibição em formato americano
     * 
     * @param float|string|null $valor
     * @param int $decimais
     * @return string
     */
    protected function formatarValorAmericano($valor, int $decimais = 2): string
    {
        if (is_null($valor)) {
            return '$0.00';
        }

        $valor = (float) $valor;
        return '$' . number_format($valor, $decimais, '.', ',');
    }
}