<?php

namespace App\Services\Bugio;

use App\Services\MailInbound\Support\DocumentIdentity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleXMLElement;

class CteXmlParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $disk, string $path): array
    {
        $content = Storage::disk($disk)->get($path);

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('Arquivo XML de CT-e nao encontrado ou vazio.');
        }

        $xml = simplexml_load_string($content);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('XML de CT-e invalido.');
        }

        $namespaces = $xml->getNamespaces(true);

        if (isset($namespaces[''])) {
            $xml->registerXPathNamespace('cte', $namespaces['']);
        }

        $infCte = $this->firstNode($xml, ['//cte:infCte', '//infCte']);

        if (! $infCte instanceof SimpleXMLElement) {
            throw new RuntimeException('Estrutura do CT-e nao encontrada.');
        }

        $tpCte = $this->value($xml, ['//cte:ide/cte:tpCTe', '//ide/tpCTe']);

        return [
            'chave_cte' => str_replace('CTe', '', (string) ($infCte['Id'] ?? '')) ?: null,
            'numero_cte' => $this->value($xml, ['//cte:ide/cte:nCT', '//ide/nCT']),
            'serie' => $this->value($xml, ['//cte:ide/cte:serie', '//ide/serie']),
            'emitido_em' => ($date = $this->value($xml, ['//cte:ide/cte:dhEmi', '//ide/dhEmi'])) ? Carbon::parse($date) : null,
            'emitente_nome' => $this->value($xml, ['//cte:emit/cte:xNome', '//emit/xNome']),
            'emitente_documento' => DocumentIdentity::normalizeDigits($this->value($xml, ['//cte:emit/cte:CNPJ', '//emit/CNPJ', '//cte:emit/cte:CPF', '//emit/CPF'])),
            'remetente_nome' => $this->value($xml, ['//cte:rem/cte:xNome', '//rem/xNome']),
            'remetente_documento' => DocumentIdentity::normalizeDigits($this->value($xml, ['//cte:rem/cte:CNPJ', '//rem/CNPJ', '//cte:rem/cte:CPF', '//rem/CPF'])),
            'destinatario_nome' => $this->value($xml, ['//cte:dest/cte:xNome', '//dest/xNome']),
            'destinatario_documento' => DocumentIdentity::normalizeDigits($this->value($xml, ['//cte:dest/cte:CNPJ', '//dest/CNPJ', '//cte:dest/cte:CPF', '//dest/CPF'])),
            'tomador_nome' => $this->value($xml, ['//cte:toma4/cte:xNome', '//toma4/xNome']),
            'tomador_documento' => DocumentIdentity::normalizeDigits($this->value($xml, ['//cte:toma4/cte:CNPJ', '//toma4/CNPJ', '//cte:toma4/cte:CPF', '//toma4/CPF'])),
            'placa_transportador' => DocumentIdentity::normalizePlate($this->value($xml, ['//cte:veic/cte:placa', '//veic/placa', '//cte:rodo/cte:veicTracao/cte:placa', '//rodo/veicTracao/placa'])),
            'valor_total' => (float) ($this->value($xml, ['//cte:vPrest/cte:vTPrest', '//vPrest/vTPrest']) ?? 0),
            'valor_receber' => (float) ($this->value($xml, ['//cte:vPrest/cte:vRec', '//vPrest/vRec']) ?? 0),
            'valor_icms' => (float) ($this->value($xml, ['//cte:ICMS00/cte:vICMS', '//ICMS00/vICMS', '//cte:ICMS20/cte:vICMS', '//ICMS20/vICMS', '//cte:ICMS45/cte:vICMS', '//ICMS45/vICMS']) ?? 0),
            'tipo_cte' => $tpCte,
            'tipo_documento' => $tpCte === '1' ? 'CTe Complemento' : 'CTe',
        ];
    }

    protected function value(SimpleXMLElement $xml, array $paths): ?string
    {
        $node = $this->firstNode($xml, $paths);

        if (! $node instanceof SimpleXMLElement) {
            return null;
        }

        $value = trim((string) $node);

        return $value !== '' ? $value : null;
    }

    protected function firstNode(SimpleXMLElement $xml, array $paths): mixed
    {
        foreach ($paths as $path) {
            $result = $xml->xpath($path);

            if (is_array($result) && $result !== []) {
                return $result[0];
            }
        }

        return null;
    }
}
