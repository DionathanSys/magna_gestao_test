<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models;
use App\Services\Veiculo\Queries\GetQuilometragemUltimoMovimento;
use App\Services\Veiculo\VeiculoService;
use Illuminate\Support\Facades\Log;

class TesteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teste-command {--id= : id do Modelo}';

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
        Log::debug('TesteCommand executado com sucesso!');
        $this->info('TesteCommand executado com sucesso!');
    }
}
