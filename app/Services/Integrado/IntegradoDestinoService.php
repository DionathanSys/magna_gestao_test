<?php

namespace App\Services\Integrado;

use App\Models\CargaViagem;
use App\Models\Integrado;
use App\Models\IntegradoAlias;
use App\Models\Viagem;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntegradoDestinoService
{
    use UserCheckTrait;

    public function vincularCarga(Viagem $viagem, ?string $destino): ?CargaViagem
    {
        $destinoNormalizado = $this->normalizar($destino);

        if ($destinoNormalizado === null) {
            return null;
        }

        $integrado = $this->resolver($destinoNormalizado);
        $carga = CargaViagem::query()->firstOrNew([
            'viagem_id' => $viagem->id,
            'destino_normalizado' => $destinoNormalizado,
        ]);

        $carga->fill([
            'documento_transporte' => $viagem->documento_transporte,
            'destino_externo' => trim($destino),
            'destino_normalizado' => $destinoNormalizado,
            'updated_by' => $this->getUserIdChecked(),
        ]);

        if (! $carga->exists) {
            $carga->created_by = $this->getUserIdChecked();
        }

        if ($integrado) {
            $carga->integrado_id = $integrado->id;
        }

        $carga->save();

        if (! $integrado) {
            Log::warning('Destino da integracao sem integrado correspondente', [
                'viagem_id' => $viagem->id,
                'numero_viagem' => $viagem->numero_viagem,
                'destino' => $destino,
            ]);
        }

        return $carga;
    }

    public function normalizar(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        $valor = Str::upper(Str::ascii($valor));
        $valor = preg_replace('/[^A-Z0-9]+/', ' ', $valor);

        return trim((string) preg_replace('/\s+/', ' ', $valor));
    }

    public function registrarAlias(Integrado $integrado, string $alias): bool
    {
        $aliasNormalizado = $this->normalizar($alias);

        if ($aliasNormalizado === null) {
            return false;
        }

        $registro = IntegradoAlias::query()
            ->where('alias_normalizado', $aliasNormalizado)
            ->first();

        if ($registro) {
            return $registro->integrado_id === $integrado->id;
        }

        $integrado->aliases()->create([
            'alias' => trim($alias),
            'alias_normalizado' => $aliasNormalizado,
        ]);

        return true;
    }

    private function resolver(string $destinoNormalizado): ?Integrado
    {
        $alias = IntegradoAlias::query()
            ->where('alias_normalizado', $destinoNormalizado)
            ->with('integrado')
            ->first();

        if ($alias) {
            return $alias->integrado;
        }

        $integrados = Integrado::query()
            ->select(['id', 'nome'])
            ->get()
            ->filter(fn (Integrado $integrado): bool => $this->normalizar($integrado->nome) === $destinoNormalizado)
            ->values();

        return $integrados->count() === 1 ? $integrados->first() : null;
    }
}
