<?php

namespace App\Services\CteService\Actions;

use App\DTO\PayloadCteDTO;
use Illuminate\Support\Facades\Validator;

class EnviarSolicitacaoCte
{

    public function __construct(protected PayloadCteDTO $payloadCteDTO)
    {

    }
    public function handle(): void
    {


    }

}
