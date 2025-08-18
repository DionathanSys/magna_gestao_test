<?php

namespace App\Services;

use App\Models\Integrado;

class IntegradoService
{
    public function buscaIntegrado(string $nome): ?Integrado
    {
        $codigoIntegrado = $this->extrairCodigoIntegrado($nome);

        return Integrado::query()->where('codigo', $codigoIntegrado)
                                ->first();
    }

    public function extrairCodigoIntegrado($nome): ?string
    {
        if (preg_match('/\((\d+)/', $nome, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function atualizarKmRota(Integrado $integrado, float $kmRota): void
    {
        $integrado->update(['km_rota' => $kmRota]);
    }

}
