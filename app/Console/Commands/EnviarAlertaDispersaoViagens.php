<?php

namespace App\Console\Commands;

use App\Mail\AlertaDispersaoViagem;
use App\Models\Viagem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarAlertaDispersaoViagens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'viagens:alertar-dispersao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia, de hora em hora, um e-mail com as viagens cuja dispersão é maior ou igual ao limite configurado. Cada viagem é notificada uma única vez.';

    public function handle(): void
    {
        $this->info('Iniciando envio do alerta de dispersão de viagens...');

        $limiteKm = (float) db_config('config-viagem.km_dispersao_minima_alerta', 4);
        $destinatarios = collect(db_config('config-viagem.emails_alerta_dispersao', []))
            ->map(fn ($item) => is_array($item) ? ($item['email'] ?? null) : $item)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($destinatarios)) {
            $this->warn('Nenhum destinatário configurado para o alerta de dispersão.');

            Log::warning('Alerta de dispersão sem destinatários configurados', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'limite_km' => $limiteKm,
            ]);

            return;
        }

        try {
            $viagens = Viagem::query()
                ->with(['veiculo:id,placa', 'cargas.integrado:id,nome'])
                ->where('km_dispersao', '>=', $limiteKm)
                ->where('ignorar', false)
                ->whereNull('dispersao_alertado_em')
                ->orderByDesc('km_dispersao')
                ->get();

            if ($viagens->isEmpty()) {
                $this->info('Nenhuma viagem acima do limite de dispersão.');

                Log::info('Alerta de dispersão: nenhuma viagem para notificar', [
                    'metodo' => __METHOD__.'@'.__LINE__,
                    'limite_km' => $limiteKm,
                    'destinatarios' => $destinatarios,
                ]);

                return;
            }

            Mail::to($destinatarios)->send(new AlertaDispersaoViagem($viagens, $limiteKm));

            Viagem::query()
                ->whereIn('id', $viagens->pluck('id'))
                ->update(['dispersao_alertado_em' => now()]);

            $this->info('Alerta de dispersão enviado para '.count($destinatarios).' destinatário(s) com '.$viagens->count().' viagem(ns).');

            Log::info('Alerta de dispersão de viagens enviado', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'destinatarios' => $destinatarios,
                'limite_km' => $limiteKm,
                'total_viagens' => $viagens->count(),
            ]);
        } catch (\Exception $e) {
            $this->error('Erro ao enviar alerta de dispersão: '.$e->getMessage());

            Log::error('Erro ao enviar alerta de dispersão de viagens', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'erro' => $e->getMessage(),
                'limite_km' => $limiteKm,
            ]);
        }
    }
}
