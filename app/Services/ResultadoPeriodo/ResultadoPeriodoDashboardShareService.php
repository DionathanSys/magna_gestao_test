<?php

namespace App\Services\ResultadoPeriodo;

use App\Models\ResultadoPeriodoCompartilhamento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class ResultadoPeriodoDashboardShareService
{
    /**
     * @return array{share: ResultadoPeriodoCompartilhamento, url: string}
     */
    public function create(
        Collection|array $resultadoPeriodos,
        string $destinatarioNome,
        ?string $destinatarioEmail,
        int $validadeHoras,
        ?int $criadoPorId,
    ): array {
        if ($validadeHoras < 1) {
            throw new \InvalidArgumentException('A validade do link deve ser maior que zero.');
        }

        $ids = collect($resultadoPeriodos)
            ->map(fn ($resultadoPeriodo): int => (int) ($resultadoPeriodo->id ?? $resultadoPeriodo))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw new \InvalidArgumentException('Selecione ao menos um resultado de período.');
        }

        $expiresAt = now()->addHours($validadeHoras);
        $token = bin2hex(random_bytes(32));
        $share = ResultadoPeriodoCompartilhamento::create([
            'token_hash' => hash('sha256', $token),
            'resultado_periodo_ids' => $ids->all(),
            'destinatario_nome' => $destinatarioNome,
            'destinatario_email' => $destinatarioEmail,
            'criado_por_id' => $criadoPorId,
            'expires_at' => $expiresAt,
        ]);

        return [
            'share' => $share,
            'url' => URL::temporarySignedRoute(
                'resultado-periodo.dashboard',
                $expiresAt,
                ['token' => $token],
            ),
        ];
    }

    public function resolve(string $token): ?ResultadoPeriodoCompartilhamento
    {
        $share = ResultadoPeriodoCompartilhamento::query()
            ->valid()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $share) {
            return null;
        }

        $share->forceFill(['last_accessed_at' => now()])->saveQuietly();

        return $share;
    }
}
