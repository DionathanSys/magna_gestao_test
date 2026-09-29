<?php

namespace App\Console\Commands;

use App\Enum\Lembrete\StatusLembreteEnum;
use App\Jobs\Telegram\EnviarLembreteTelegram;
use App\Models\Lembrete;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessarLembretesTelegram extends Command
{
    protected $signature = 'telegram:processar-lembretes {--dry-run : Lista os lembretes vencidos sem enfileirar envios}';

    protected $description = 'Enfileira alertas e lembretes do Telegram que chegaram ao horário programado.';

    public function handle(): int
    {
        $lembretes = Lembrete::query()
            ->with('user:id,name,telegram_chat_id')
            ->due()
            ->orderBy('scheduled_at')
            ->orderBy('id');

        if ($this->option('dry-run')) {
            $total = $lembretes->count();
            $this->info("Lembretes vencidos: {$total}.");

            return self::SUCCESS;
        }

        if (! (bool) config('services.telegram.enabled', false) || blank(config('services.telegram.bot_token'))) {
            $this->warn('Telegram não está configurado. Nenhum lembrete foi processado.');

            return self::SUCCESS;
        }

        $enfileirados = 0;

        $lembretes->chunkById(100, function ($itens) use (&$enfileirados): void {
            foreach ($itens as $lembrete) {
                $claimed = Lembrete::query()
                    ->whereKey($lembrete->id)
                    ->where('status', StatusLembreteEnum::PENDENTE->value)
                    ->update(['status' => StatusLembreteEnum::ENVIANDO->value]);

                if ($claimed !== 1) {
                    continue;
                }

                try {
                    EnviarLembreteTelegram::dispatch($lembrete->id);
                    $enfileirados++;
                } catch (Throwable $exception) {
                    Lembrete::query()->whereKey($lembrete->id)->update([
                        'status' => StatusLembreteEnum::FALHOU->value,
                        'error_message' => $exception->getMessage(),
                    ]);

                    Log::error('Falha ao enfileirar lembrete do Telegram', [
                        'lembrete_id' => $lembrete->id,
                        'erro' => $exception->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Lembretes enfileirados: {$enfileirados}.");

        return self::SUCCESS;
    }
}
