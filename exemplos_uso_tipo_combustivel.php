<?php

use App\Enum\Abastecimento\TipoCombustivelEnum;

// Exemplo de uso do método fromProductCode
$codigo = 1; // ou '1', 'S10', 'DIESEL_S10'
$tipo = TipoCombustivelEnum::fromProductCode($codigo);

if ($tipo) {
    echo "Tipo encontrado: " . $tipo->value . PHP_EOL;
} else {
    echo "Tipo não encontrado para o código: {$codigo}\n";
}

// Exemplo usando fallback
$tipoFallback = TipoCombustivelEnum::fromProductCodeOrDefault('unknown', TipoCombustivelEnum::DIESEL_S10_POSTOS);
echo "Tipo com fallback: " . $tipoFallback->value . PHP_EOL;
