<?php

namespace App\Services\Checklist\Actions;

class MutateDataChecklist
{
    public function handle(array $data): array
    {
        ds($data)->label('Dados recebidos para mutação');
        


        return $data;
    }
}
