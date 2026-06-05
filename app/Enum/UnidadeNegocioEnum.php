<?php

namespace App\Enum;

enum UnidadeNegocioEnum: string
{
    case CHAPECO = 'CHAPECO';
    case CATANDUVAS = 'CATANDUVAS';
    case CONCORDIA = 'CONCORDIA';

    public static function toSelectArray(): array
    {
        return [
            self::CHAPECO->value => 'Chapeco',
            self::CATANDUVAS->value => 'Catanduvas',
            self::CONCORDIA->value => 'Concordia',
        ];
    }
}
