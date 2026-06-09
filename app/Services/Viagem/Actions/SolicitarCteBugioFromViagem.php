<?php

namespace App\Services\Viagem\Actions;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\Integrado;
use App\Models\Viagem;
use App\Services\ViagemBugio\ViagemBugioService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SolicitarCteBugioFromViagem
{
    public function handle(Viagem $viagem, array $data): void
    {
        $viagem->loadMissing([
            'veiculo',
            'cargas.integrado',
            'attachments.incomingEmailAttachment',
            'attachments.receivedFiscalDocument',
        ]);

        $integrado = Integrado::query()->findOrFail($data['integrado_id']);
        $motoristaCpf = $data['motorista'];
        $motoristaNome = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $motoristaCpf)['motorista'] ?? null;

        $anexos = $viagem->attachments
            ->map(fn ($attachment) => $attachment->incomingEmailAttachment)
            ->filter()
            ->pluck('path')
            ->filter(fn (?string $path) => filled($path) && Storage::disk('local')->exists($path))
            ->unique()
            ->values()
            ->all();

        if ($anexos === []) {
            throw new \InvalidArgumentException('A viagem não possui anexos válidos para solicitar CTe.');
        }

        $nroNotas = $viagem->attachments
            ->map(fn ($attachment) => $attachment->receivedFiscalDocument?->numero_nota)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($nroNotas === []) {
            throw new \InvalidArgumentException('A viagem não possui notas fiscais vinculadas nos anexos.');
        }

        $payload = [
            'veiculo_id' => $viagem->veiculo_id,
            'destinos' => [[
                'integrado_id' => $integrado->id,
                'km_rota' => (float) ($integrado->km_rota ?? 0),
                'integrado_nome' => $integrado->nome,
            ]],
            'km_rodado' => 0,
            'km_pago' => (float) ($viagem->km_pago ?? 0),
            'data_competencia' => optional($viagem->data_competencia)->format('Y-m-d') ?: (string) $viagem->data_competencia,
            'frete' => (float) (($viagem->km_pago ?? 0) * db_config('config-bugio.valor-quilometro', 0)),
            'condutor' => $motoristaNome,
            'observacao' => 'Solicitação de CTe criada a partir da viagem ' . $viagem->numero_viagem,
            'status' => 'pendente',
            'created_by' => Auth::id() ?? $viagem->created_by,
            'nro_notas' => $nroNotas,
            'anexos' => $anexos,
            'info_adicionais' => [
                'tipo_documento' => ($data['cte_complementar'] ?? false)
                    ? TipoDocumentoEnum::CTE_COMPLEMENTO->value
                    : TipoDocumentoEnum::CTE->value,
                'cte_retroativo' => (bool) ($data['cte_retroativo'] ?? true),
                'cte_referencia' => $data['cte_referencia'] ?? null,
                'motorista-cpf' => $motoristaCpf,
                'origem' => 'viagem',
                'viagem_id_origem' => $viagem->id,
            ],
        ];

        Log::info('Criando solicitação Bugio a partir de viagem', [
            'viagem_id' => $viagem->id,
            'numero_viagem' => $viagem->numero_viagem,
            'integrado_id' => $integrado->id,
            'nro_notas' => $nroNotas,
            'anexos_count' => count($anexos),
        ]);

        $viagemBugio = (new ViagemBugioService())->criarViagem($payload);

        if (! $viagemBugio) {
            throw new \RuntimeException('Não foi possível criar a solicitação Bugio a partir da viagem.');
        }

        $viagemBugio->update([
            'viagem_id' => $viagem->id,
        ]);
    }
}
