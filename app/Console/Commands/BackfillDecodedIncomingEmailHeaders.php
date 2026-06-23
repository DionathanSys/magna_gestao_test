<?php

namespace App\Console\Commands;

use App\Models\IncomingEmail;
use Illuminate\Console\Command;

class BackfillDecodedIncomingEmailHeaders extends Command
{
    protected $signature = 'mail:backfill-decoded-headers {--dry-run : Apenas mostra quantos registros seriam atualizados} {--chunk=500 : Quantidade de registros por lote}';

    protected $description = 'Decodifica subject e from_name MIME ja persistidos nos emails recebidos';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $scanned = 0;
        $updated = 0;

        IncomingEmail::query()
            ->where(function ($query): void {
                $query->where('subject', 'like', '%=?%')
                    ->orWhere('from_name', 'like', '%=?%');
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($emails) use ($dryRun, &$scanned, &$updated): void {
                foreach ($emails as $email) {
                    $scanned++;

                    $newSubject = $this->decodeMimeHeader($email->subject);
                    $newFromName = $this->decodeMimeHeader($email->from_name);

                    $changes = array_filter([
                        'subject' => $newSubject !== $email->subject ? $newSubject : null,
                        'from_name' => $newFromName !== $email->from_name ? $newFromName : null,
                    ], fn ($value): bool => $value !== null);

                    if ($changes === []) {
                        continue;
                    }

                    $updated++;

                    if ($dryRun) {
                        $this->line(sprintf(
                            '[dry-run] incoming_email_id=%d subject=%s from_name=%s',
                            $email->id,
                            array_key_exists('subject', $changes) ? 'sim' : 'nao',
                            array_key_exists('from_name', $changes) ? 'sim' : 'nao',
                        ));

                        continue;
                    }

                    $email->forceFill($changes)->save();
                }
            });

        $this->info(sprintf(
            'Backfill concluido. Registros analisados: %d. Registros %s: %d.',
            $scanned,
            $dryRun ? 'que seriam atualizados' : 'atualizados',
            $updated,
        ));

        return self::SUCCESS;
    }

    protected function decodeMimeHeader(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $value;
        }

        if (! str_contains($value, '=?')) {
            return $value;
        }

        $decoded = iconv_mime_decode($value, 0, 'UTF-8');

        return $decoded !== false ? $decoded : $value;
    }
}
