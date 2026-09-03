<?php

namespace Tests\Feature;

use App\Services\Bugio\CteXmlParser;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CteXmlParserTest extends TestCase
{
    public function test_extracts_all_referenced_nfe_keys(): void
    {
        Storage::fake('local');

        $firstKey = '35260912345678000123550010000000011000000010';
        $secondKey = '35260912345678000123550010000000021000000020';

        Storage::disk('local')->put('cte/return.xml', <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <CTe xmlns="http://www.portalfiscal.inf.br/cte">
                <infCte Id="CTe35260912345678000123570010000000011000000010">
                    <ide><tpCTe>0</tpCTe><nCT>1</nCT></ide>
                    <infDoc>
                        <infNFe><chave>{$firstKey}</chave></infNFe>
                        <infNFe><chave>{$secondKey}</chave></infNFe>
                    </infDoc>
                </infCte>
            </CTe>
            XML);

        $parsed = app(CteXmlParser::class)->parse('local', 'cte/return.xml');

        $this->assertSame([$firstKey, $secondKey], $parsed['referenced_nfe_keys']);
    }
}
