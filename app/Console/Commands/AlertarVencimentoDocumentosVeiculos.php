<?php

namespace App\Console\Commands;

use App\Mail\AlertaVencimentoDocumentosVeiculosMail;
use App\Models\VeiculoDocumento;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertarVencimentoDocumentosVeiculos extends Command
{
    protected $signature = 'documentos-veiculos:alertar-vencimentos {--dry-run : Lista os alertas sem enviar e-mail}';

    protected $description = 'Envia alertas de vencimento de documentos de veículos conforme configuração por tipo e unidade.';

    public function handle(): int
    {
        $regras = collect(db_config('config-veiculo.alertas_documentos', []))
            ->filter(fn (array $regra): bool => ($regra['ativo'] ?? true) && filled($regra['tipo'] ?? null));

        if ($regras->isEmpty()) {
            $this->info('Nenhuma regra de alerta configurada.');

            return self::SUCCESS;
        }

        $totalEnviados = 0;

        foreach ($regras as $index => $regra) {
            $emails = $this->normalizarEmails($regra['emails'] ?? []);
            $unidades = array_values(array_filter($regra['unidades'] ?? []));

            if ($emails === [] || $unidades === []) {
                $this->warn('Regra '.($index + 1).' ignorada por falta de e-mails ou unidades.');

                continue;
            }

            $documentos = $this->buscarDocumentos($regra['tipo'], $unidades);

            if ($documentos->isEmpty()) {
                $this->line('Regra '.($index + 1).': nenhum documento para alertar.');

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line('Regra '.($index + 1).': '.$documentos->count().' documento(s) seriam enviados para '.implode(', ', $emails).'.');

                continue;
            }

            Mail::to($emails)->send(new AlertaVencimentoDocumentosVeiculosMail($documentos, $regra));

            $totalEnviados++;

            Log::info('Alerta de vencimento de documentos de veículos enviado', [
                'tipo' => $regra['tipo'],
                'unidades' => $unidades,
                'emails' => $emails,
                'total_documentos' => $documentos->count(),
            ]);
        }

        $this->info($this->option('dry-run') ? 'Dry-run concluído.' : "Envios realizados: {$totalEnviados}");

        return self::SUCCESS;
    }

    private function buscarDocumentos(string $tipo, array $unidades): Collection
    {
        return VeiculoDocumento::query()
            ->with('veiculo:id,placa,filial')
            ->where('tipo', $tipo)
            ->whereNotNull('data_fim')
            ->whereHas('veiculo', fn (Builder $query): Builder => $query->whereIn('filial', $unidades))
            ->whereRaw('DATEDIFF(data_fim, CURDATE()) <= dias_alerta')
            ->orderBy('data_fim')
            ->orderBy('id')
            ->get();
    }

    private function normalizarEmails(array $emails): array
    {
        return collect($emails)
            ->map(fn ($email): ?string => is_array($email) ? ($email['email'] ?? null) : $email)
            ->filter(fn ($email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }
}
