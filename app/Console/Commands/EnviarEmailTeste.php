<?php

namespace App\Console\Commands;

use App\Mail\TesteMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarEmailTeste extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:enviar-email-teste';

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
        Mail::to('dionathan.silva@transmagnabosco.com.br')
            ->cc('dionideev@gmail.com')
            ->send(new TesteMail());
        $this->info('Email de teste enviado com sucesso!');
        return Command::SUCCESS;
    }
}
