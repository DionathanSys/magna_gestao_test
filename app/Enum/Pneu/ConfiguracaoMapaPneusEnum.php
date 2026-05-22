<?php

namespace App\Enum\Pneu;

enum ConfiguracaoMapaPneusEnum: string
{
    case CAMINHAO_6X2 = '6x2';
    case CAMINHAO_8X2 = '8x2';

    public function label(): string
    {
        return match ($this) {
            self::CAMINHAO_6X2 => 'Caminhão 6x2',
            self::CAMINHAO_8X2 => 'Caminhão 8x2',
        };
    }

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $item) => [$item->value => $item->label()])
            ->toArray();
    }
}
