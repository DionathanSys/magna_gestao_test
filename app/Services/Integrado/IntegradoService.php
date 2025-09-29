<?php

namespace App\Services\Integrado;

use App\Models;
use App\Services;
use Illuminate\Support\Facades\Log;

class IntegradoService
{
    public function getKmCadastroIntegrado() {}

    public function buscaIntegrado(string $nome): ?Models\Integrado
    {
        if ($nome) {
            $codigoIntegrado = $this->extrairCodigoIntegrado($nome);

            return Models\Integrado::query()->where('codigo', $codigoIntegrado)
                ->first();
        } else {
            Log::alert("10 - Nome do integrado vazio ao buscar integrado.");
        }

        return null;
    }

    public function getIntegradoByCodigo(string $codigo): ?Models\Integrado
    {
        return Models\Integrado::query()->where('codigo', $codigo)->first();
    }

    public function extrairCodigoIntegrado(string $nome): ?string
    {
        if (stripos($nome, 'BRF') !== false) {
            return 0;
        }

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
