<?php

namespace App\Services\CteService\Actions;

use App\DTO\PayloadCteDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EnviarSolicitacaoCte
{

    public function handle(PayloadCteDTO $payloadCteDTO): void
    {
        Log::debug('antes do mail'. __METHOD__.'@'.__LINE__);
        Mail::send(new \App\Mail\SolicitacaoCteMail($payloadCteDTO));
        Log::debug('depois do mail'. __METHOD__.'@'.__LINE__);
    }

}
