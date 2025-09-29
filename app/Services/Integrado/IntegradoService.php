<?php

namespace App\Services\Integrado;

use App\Models;
use App\Services;

class IntegradoService
{
    public function getKmCadastroIntegrado()
    {

    }

    public function buscaIntegrado(string $nome): ?Models\Integrado
    {
        if (empty($nome) || !$nome){
            return null;
        }

        $codigoIntegrado = $this->extrairCodigoIntegrado($nome);

        return Models\Integrado::query()->where('codigo', $codigoIntegrado)
                                ->first();
    }

    public function getIntegradoByCodigo(string $codigo): ?Models\Integrado
    {
        return Models\Integrado::query()->where('codigo', $codigo)->first();
    }

    public function extrairCodigoIntegrado(string $nome): ?string
    {
        if (preg_match('/\((\d+)/', $nome, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function atualizarKmRota(Models\Integrado $integrado, float $kmRota): void
    {
        $integrado->update(['km_rota' => $kmRota]);
    }
}
