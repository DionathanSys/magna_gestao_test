<?php

namespace App\Enum;

enum MotivoDivergenciaViagem: string
{
    case SEM_OBS                    = 'SEM OBSERVAÇÃO';
    case KM_ROTA_DIVERGENTE         = 'KM ROTA DIVERGENTE';
    case ERRO_DE_TRAJETO            = 'ERRO DE TRAJETO';
    case TRAJETO_NAO_PLANEJADO      = 'TRAJETO NÃO PLANEJADO';
    case TRAJETO_DIVERGENTE         = 'TRAJETO DIVERGENTE';
    case DESVIO_JUSTIFICADO         = 'DESVIO JUSTIFICADO';
    case DESVIO_NAO_JUSTIFICADO     = 'DESVIO NÃO JUSTIFICADO';
    case DESLOCAMENTO_GARAGEM       = 'DESLOCAMENTO GARAGEM';
    case DESLOCAMENTO_MANT_INT      = 'MANUTENÇÃO INTERNA';
    case DESLOCAMENTO_MANT_EXT      = 'MANUTENÇÃO EXTERNA';
    case DESLOCAMENTO_LAVACAO       = 'DESLOCAMENTO LAVAÇÃO';
    case DESLOCAMENTO_OUTROS        = 'DESLOCAMENTO OUTROS';
    case RETORNO_VEICULO_QUEBRADO   = 'RETORNO VEÍCULO QUEBRADO';
    case AUX_BALDEIO_CARGA          = 'AUX. EM BALDEIO DE CARGA';
    case SEM_VIAGEM                 = 'SEM VIAGEM';

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($item) => [$item->value => $item->value])
            ->toArray();
    }
}
