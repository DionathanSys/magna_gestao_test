<?php

namespace App\Services\MailInbound;

use App\Services\MailInbound\Support\DocumentIdentity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleXMLElement;

class NfeXmlParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $disk, string $path): array
    {
        $content = Storage::disk($disk)->get($path);
        $xml = simplexml_load_string($content);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('XML inválido para processamento.');
        }

        $namespaces = $xml->getNamespaces(true);
        if (isset($namespaces[''])) {
            $xml->registerXPathNamespace('nfe', $namespaces['']);
        }

        $infNFe = $this->firstNode($xml, ['//nfe:infNFe', '//infNFe']);
        if (! $infNFe instanceof SimpleXMLElement) {
            throw new RuntimeException('Estrutura da NFe não encontrada.');
        }

        $volumes = $this->allNodes($xml, ['//nfe:transp/nfe:vol', '//transp/vol']);
        $pesoCarga = collect($volumes)
            ->map(fn (SimpleXMLElement $volume) => (float) ($volume->pesoB ?: $volume->pesoL ?: 0))
            ->sum();

        $emitidoEm = $this->value($xml, ['//nfe:ide/nfe:dhEmi', '//ide/dhEmi', '//nfe:ide/nfe:dEmi', '//ide/dEmi']);

        return [
            'chave_nfe' => str_replace('NFe', '', (string) ($infNFe['Id'] ?? '')) ?: null,
            'numero_nota' => $this->value($xml, ['//nfe:ide/nfe:nNF', '//ide/nNF']),
            'serie' => $this->value($xml, ['//nfe:ide/nfe:serie', '//ide/serie']),
            'emitido_em' => $emitidoEm ? Carbon::parse($emitidoEm) : null,
            'destinatario_nome' => $this->value($xml, ['//nfe:dest/nfe:xNome', '//dest/xNome']),
            'destinatario_cnpj' => DocumentIdentity::normalizeDigits($this->value($xml, ['//nfe:dest/nfe:CNPJ', '//dest/CNPJ', '//nfe:dest/nfe:CPF', '//dest/CPF'])),
            'transportador_nome' => $this->value($xml, ['//nfe:transp/nfe:transporta/nfe:xNome', '//transp/transporta/xNome']),
            'transportador_cnpj' => DocumentIdentity::normalizeDigits($this->value($xml, ['//nfe:transp/nfe:transporta/nfe:CNPJ', '//transp/transporta/CNPJ', '//nfe:transp/nfe:transporta/nfe:CPF', '//transp/transporta/CPF'])),
            'placa_transportador' => DocumentIdentity::normalizePlate($this->value($xml, ['//nfe:transp/nfe:veicTransp/nfe:placa', '//transp/veicTransp/placa'])),
            'peso_carga' => $pesoCarga > 0 ? $pesoCarga : null,
            'referenced_nfe_key' => str_replace('NFe', '', (string) ($this->firstNode($xml, ['//nfe:NFref/nfe:refNFe', '//NFref/refNFe']) ?? '')) ?: null,
            'inf_adic' => $this->value($xml, ['//nfe:infAdic/nfe:infCpl', '//infAdic/infCpl']),
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

    /**
     * @return array<int, SimpleXMLElement>
     */
    protected function allNodes(SimpleXMLElement $xml, array $paths): array
    {
        foreach ($paths as $path) {
            $result = $xml->xpath($path);

            if (is_array($result) && $result !== []) {
                return $result;
            }
        }

        return [];
    }
}
