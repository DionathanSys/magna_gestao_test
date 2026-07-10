<?php

namespace App\Enum\OrdemServico;

enum PosicaoItemOrdemServicoEnum: string
{
    case DD = 'DD';
    case DE = 'DE';
    case TD = 'TD';
    case TE = 'TE';
    case DT = 'DT';
    case TR = 'TR';
    case POS_2DD = '2DD';
    case POS_2DE = '2DE';
    case POS_2TD = '2TD';
    case POS_2TE = '2TE';
    case POS_2DT = '2DT';
    case POS_2TR = '2TR';
    case POS_3DD = '3DD';
    case POS_3DE = '3DE';
    case POS_3TD = '3TD';
    case POS_3TE = '3TE';
    case POS_3DT = '3DT';
    case POS_3TR = '3TR';
    case POS_4DD = '4DD';
    case POS_4DE = '4DE';
    case POS_4TD = '4TD';
    case POS_4TE = '4TE';
    case POS_4DT = '4DT';
    case POS_4TR = '4TR';

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
