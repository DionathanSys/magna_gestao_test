<?php

namespace Tests\Unit;

use App\Services\Viagem\Actions\SolicitarCteBugioFromViagem;
use PHPUnit\Framework\TestCase;

class AutomaticCteRulesTest extends TestCase
{
    public function test_identifies_guatambu_municipio_variations(): void
    {
        $service = new SolicitarCteBugioFromViagem;

        $this->assertTrue($service->isGuatambuMunicipio('GUATAMBU'));
        $this->assertTrue($service->isGuatambuMunicipio('Guatambú'));
        $this->assertTrue($service->isGuatambuMunicipio('Guata-mbu'));
        $this->assertFalse($service->isGuatambuMunicipio('Chapecó'));
    }
}
