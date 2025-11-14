<?php

namespace App\Console\Commands;

use App\Mail\RelatoriodiarioMail;
use App\Models\Agendamento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarEmailDiario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:diario';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando envio do email diário...');

        try {
            // Emails destinatários
            $emailsOption = $this->option('emails');
            $emails = $emailsOption
                ? array_map('trim', explode(',', $emailsOption))
                : ['dionathan.silva@transmagnabosco.com.br'];

            // Coletar dados para o email
            $dados = $this->coletarDadosAgendamentos();

            // Enviar email para cada destinatário
            foreach ($emails as $email) {
                Mail::to($email)->send(new RelatoriodiarioMail($dados));
                $this->info("Email enviado para: {$email}");
            }

            Log::info('Email diário de agendamentos enviado com sucesso', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'destinatarios' => $emails,
                'total_agendamentos' => array_sum([
                    count($dados['pendentes']),
                    count($dados['em_execucao']),
                    count($dados['amanha']),
                    count($dados['esta_semana']),
                    count($dados['atrasados']),
                ]),
            ]);

            $this->info('Email diário enviado com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao enviar email: ' . $e->getMessage());

            Log::error('Erro ao enviar email diário', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function coletarDadosAgendamentos(): array
    {
        $hoje = Carbon::today();
        $amanha = Carbon::tomorrow();
        $inicioSemana = Carbon::now()->startOfWeek();
        $fimSemana = Carbon::now()->endOfWeek();

        return [
            'data_relatorio' => $hoje->format('d/m/Y'),
            'data_geracao' => now()->format('d/m/Y H:i'),

            // Agendamentos pendentes (hoje)
            'pendentes' => $this->buscarAgendamentos([
                ['data_agendamento', '=', $hoje->toDateString()],
                ['status', '=', 'PENDENTE']
            ]),

            // Agendamentos em execução (hoje)
            'em_execucao' => $this->buscarAgendamentos([
                ['data_agendamento', '=', $hoje->toDateString()],
                ['status', '=', 'EM_EXECUCAO']
            ]),

            // Agendamentos para amanhã
            'amanha' => $this->buscarAgendamentos([
                ['data_agendamento', '=', $amanha->toDateString()],
                ['status', 'in', ['PENDENTE', 'EM_EXECUCAO']]
            ]),

            // Agendamentos desta semana (exceto hoje e amanhã)
            'esta_semana' => $this->buscarAgendamentos([
                ['data_agendamento', '>', $amanha->toDateString()],
                ['data_agendamento', '<=', $fimSemana->toDateString()],
                ['status', 'in', ['PENDENTE', 'EM_EXECUCAO']]
            ]),

            // Agendamentos atrasados
            'atrasados' => $this->buscarAgendamentos([
                ['data_agendamento', '<', $hoje->toDateString()],
                ['status', 'in', ['PENDENTE', 'EM_EXECUCAO']]
            ]),

            // Resumo estatístico
            'resumo' => $this->gerarResumo(),
        ];
    }

    private function buscarAgendamentos(array $filtros): array
    {
        try {
            $query = Agendamento::query()
                ->with(['veiculo:id,placa', 'responsavel:id,name', 'tipoServico:id,nome']);

            foreach ($filtros as $filtro) {
                if (isset($filtro[2]) && $filtro[1] === 'in') {
                    $query->whereIn($filtro[0], $filtro[2]);
                } else {
                    $query->where($filtro[0], $filtro[1], $filtro[2] ?? null);
                }
            }

            return $query->orderBy('data_agendamento')
                ->orderBy('hora_inicio')
                ->get()
                ->map(function ($agendamento) {
                    return [
                        'id' => $agendamento->id,
                        'data_agendamento' => $agendamento->data_agendamento?->format('d/m/Y'),
                        'hora_inicio' => $agendamento->hora_inicio,
                        'hora_fim' => $agendamento->hora_fim,
                        'veiculo_placa' => $agendamento->veiculo?->placa ?? 'N/A',
                        'responsavel_nome' => $agendamento->responsavel?->name ?? 'N/A',
                        'tipo_servico' => $agendamento->tipoServico?->nome ?? $agendamento->tipo_servico,
                        'status' => $agendamento->status,
                        'prioridade' => $agendamento->prioridade ?? 'NORMAL',
                        'observacoes' => $agendamento->observacoes,
                        'dias_atraso' => $agendamento->data_agendamento
                            ? Carbon::parse($agendamento->data_agendamento)->diffInDays(Carbon::today(), false)
                            : 0,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar agendamentos', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'filtros' => $filtros,
                'erro' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function gerarResumo(): array
    {
        try {
            $hoje = Carbon::today();

            return [
                'total_agendamentos_hoje' => Agendamento::whereDate('data_agendamento', $hoje)->count(),
                'total_pendentes' => Agendamento::where('status', 'PENDENTE')->count(),
                'total_em_execucao' => Agendamento::where('status', 'EM_EXECUCAO')->count(),
                'total_atrasados' => Agendamento::where('data_agendamento', '<', $hoje)
                    ->whereIn('status', ['PENDENTE', 'EM_EXECUCAO'])
                    ->count(),
                'total_concluidos_hoje' => Agendamento::whereDate('data_agendamento', $hoje)
                    ->where('status', 'CONCLUIDO')
                    ->count(),
                'veiculos_com_agendamento' => Agendamento::whereDate('data_agendamento', $hoje)
                    ->distinct('veiculo_id')
                    ->count('veiculo_id'),
            ];
        } catch (\Exception $e) {
            Log::warning('Erro ao gerar resumo', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'erro' => $e->getMessage(),
            ]);

            return [
                'total_agendamentos_hoje' => 0,
                'total_pendentes' => 0,
                'total_em_execucao' => 0,
                'total_atrasados' => 0,
                'total_concluidos_hoje' => 0,
                'veiculos_com_agendamento' => 0,
            ];
        }
    }

    //     sudo supervisorctl stop magna_gestao-queue:*
    // sudo supervisorctl reread
    // sudo supervisorctl update
    // sudo supervisorctl start magna_gestao-queue:*
    // sudo supervisorctl start magna_gestao-schedule:*
}
