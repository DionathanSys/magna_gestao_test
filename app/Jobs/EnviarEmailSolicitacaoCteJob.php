<?php

namespace App\Jobs;

use App\DTO\PayloadCteDTO;
use App\Mail\SolicitacaoCteMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class EnviarEmailSolicitacaoCteJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public PayloadCteDTO $payloadCteDTO)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::send(new SolicitacaoCteMail($this->payloadCteDTO));
    }
}
