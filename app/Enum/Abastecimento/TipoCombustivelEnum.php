<?php

namespace App\Enum\Abastecimento;

enum TipoCombustivelEnum: string
{
    case DIESEL_S10         = 'OLEO DIESEL B S10';
    case DIESEL_S10_POSTOS  = 'OLEO DIESEL B S10 POSTOS';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }

    /**
     * Retorna o enum correspondente ao código do produto (código do diesel).
     *
     * @param int|string $codigoProduto
     * @return self|null
     */
    public static function fromProductCode(int|string $codigoProduto): ?self
    {
        //TODO Ajustar para obter os códigos apartir do db_config
        
        // normaliza para string sem espaços
        $code = (string) $codigoProduto;

        switch (trim($code)) {
            case '158':
            case '1488':
                return self::DIESEL_S10;

            default:
                return null;
        }
    }

    /**
     * Retorna o enum correspondente ao código do produto ou um fallback (default).
     *
     * @param int|string $codigoProduto
     * @param self|null $default
     * @return self
     */
    public static function fromProductCodeOrDefault(int|string $codigoProduto, ?self $default = null): self
    {
        return static::fromProductCode($codigoProduto) ?? ($default ?? self::DIESEL_S10);
    }
}