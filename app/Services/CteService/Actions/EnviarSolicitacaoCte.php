<?php

namespace App\Services\CteService\Actions;

use App\DTO\PayloadCteDTO;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EnviarSolicitacaoCte
{

    public function handle(PayloadCteDTO $payloadCteDTO): void
    {
        Mail::send(new \App\Mail\SolicitacaoCteMail($payloadCteDTO));
    }

}
