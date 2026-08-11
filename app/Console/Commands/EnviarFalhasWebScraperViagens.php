<?php

namespace App\Console\Commands;

use App\Mail\WebScraperViagensFalhasMail;
use App\Services\WebScraper\WebScraperViagemErrorCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarFalhasWebScraperViagens extends Command
{
    protected $signature = 'webscraper:viagens:enviar-falhas {--dry-run : Lista os erros sem enviar e sem limpar o cache}';

    protected $description = 'Envia por e-mail as falhas acumuladas no processamento de viagens recebidas do WebScraper.';

    public function handle(WebScraperViagemErrorCache $errorCache): int
    {
        if ($this->option('dry-run')) {
            $this->info('Erros acumulados: '.$errorCache->count());

            return self::SUCCESS;
        }

        $errors = $errorCache->drain();

        if ($errors === []) {
            $this->info('Nenhuma falha acumulada para enviar.');

            return self::SUCCESS;
        }

        $email = (string) config('services.webscraper.error_notification_email');

        try {
            Mail::to($email)->send(new WebScraperViagensFalhasMail($errors));

            Log::info('E-mail de falhas WebScraper enviado', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'email' => $email,
                'total_erros' => count($errors),
                'lotes' => collect($errors)->pluck('lote_id')->filter()->unique()->values()->all(),
            ]);

            $this->info('E-mail enviado com '.count($errors).' falha(s).');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            foreach ($errors as $error) {
                $errorCache->add($error);
            }

            Log::error('Falha ao enviar e-mail de erros WebScraper; erros devolvidos ao cache', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'email' => $email,
                'total_erros' => count($errors),
                'error' => $exception->getMessage(),
            ]);

            $this->error('Falha ao enviar e-mail: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
