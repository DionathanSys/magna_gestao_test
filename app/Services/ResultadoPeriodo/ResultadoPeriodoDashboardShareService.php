<?php

namespace App\Services\ResultadoPeriodo;

use App\Models\ResultadoPeriodoCompartilhamento;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;

class ResultadoPeriodoDashboardShareService
{
    /**
     * @return array{share: ResultadoPeriodoCompartilhamento, url: string}
     */
    public function create(
        CarbonInterface|string $dataInicio,
        CarbonInterface|string $dataFim,
        string $destinatarioNome,
        string $destinatarioEmail,
        int $validadeHoras,
        ?int $criadoPorId,
    ): array {
        if ($validadeHoras < 1) {
            throw new \InvalidArgumentException('A validade do link deve ser maior que zero.');
        }

        $dataInicio = Carbon::parse($dataInicio)->startOfDay();
        $dataFim = Carbon::parse($dataFim)->startOfDay();

        if ($dataInicio->isAfter($dataFim)) {
            throw new \InvalidArgumentException('A data inicial deve ser anterior ou igual à data final.');
        }

        $expiresAt = now()->addHours($validadeHoras);
        $token = bin2hex(random_bytes(32));
        $share = ResultadoPeriodoCompartilhamento::create([
            'token_hash' => hash('sha256', $token),
            'resultado_periodo_ids' => [],
            'data_inicio' => $dataInicio->toDateString(),
            'data_fim' => $dataFim->toDateString(),
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
