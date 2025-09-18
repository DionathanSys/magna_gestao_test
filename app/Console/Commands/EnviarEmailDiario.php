<?php

namespace App\Console\Commands;

use App\Mail\RelatoriodiarioMail;
use Illuminate\Console\Command;
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
            // Coletar dados para o email
            $dados = $this->coletarDados();

            // Enviar email
            Mail::to('dionathan.silva@transmagnabosco.com.br')
                ->send(new RelatoriodiarioMail($dados));

            $this->info('Email diário enviado com sucesso!');

        } catch (\Exception $e) {
            $this->error('Erro ao enviar email: ' . $e->getMessage());
        }
    }

    private function coletarDados(): array
    {
        // Aqui você coleta os dados que quer enviar
        return [
            'data' => now()->format('d/m/Y'),
            'total_usuarios' => \App\Models\User::count(),
            'total_ordens' => \App\Models\OrdemServico::whereDate('created_at', today())->count(),
            // Adicione mais dados conforme necessário
        ];
    }
}
