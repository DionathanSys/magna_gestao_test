<?php

namespace App\Enum\OrdemServico;

enum PosicaoItemOrdemServicoEnum: string
{
    case EIXO_1_ESQUERDA = '1o Eixo - Esquerda';
    case EIXO_1_DIREITA = '1o Eixo - Direita';
    case EIXO_2_EXTERNA_ESQUERDA = '2o Eixo - Externa Esquerda';
    case EIXO_2_INTERNA_ESQUERDA = '2o Eixo - Interna Esquerda';
    case EIXO_2_INTERNA_DIREITA = '2o Eixo - Interna Direita';
    case EIXO_2_EXTERNA_DIREITA = '2o Eixo - Externa Direita';
    case EIXO_3_EXTERNA_ESQUERDA = '3o Eixo - Externa Esquerda';
    case EIXO_3_INTERNA_ESQUERDA = '3o Eixo - Interna Esquerda';
    case EIXO_3_INTERNA_DIREITA = '3o Eixo - Interna Direita';
    case EIXO_3_EXTERNA_DIREITA = '3o Eixo - Externa Direita';
    case EIXO_4_EXTERNA_ESQUERDA = '4o Eixo - Externa Esquerda';
    case EIXO_4_INTERNA_ESQUERDA = '4o Eixo - Interna Esquerda';
    case EIXO_4_INTERNA_DIREITA = '4o Eixo - Interna Direita';
    case EIXO_4_EXTERNA_DIREITA = '4o Eixo - Externa Direita';
    case EIXO_5_EXTERNA_ESQUERDA = '5o Eixo - Externa Esquerda';
    case EIXO_5_INTERNA_ESQUERDA = '5o Eixo - Interna Esquerda';
    case EIXO_5_INTERNA_DIREITA = '5o Eixo - Interna Direita';
    case EIXO_5_EXTERNA_DIREITA = '5o Eixo - Externa Direita';
    case EIXO_6_EXTERNA_ESQUERDA = '6o Eixo - Externa Esquerda';
    case EIXO_6_INTERNA_ESQUERDA = '6o Eixo - Interna Esquerda';
    case EIXO_6_INTERNA_DIREITA = '6o Eixo - Interna Direita';
    case EIXO_6_EXTERNA_DIREITA = '6o Eixo - Externa Direita';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $item) => [$item->value => $item->value])
            ->toArray();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
