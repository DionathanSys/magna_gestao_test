# API de Integracao WebScraper - Viagens

## Endpoint

`POST /api/integracoes/viagens`

Quando a assinatura e o payload forem validos, a API retorna `200` e enfileira o processamento. A criacao/atualizacao da viagem ocorre de forma assincrona via queue.

## Headers obrigatorios

- `Content-Type: application/json`
- `X-Webhook-Timestamp: <unix timestamp em segundos>`
- `X-Webhook-Signature: sha256=<assinatura>`
- `X-Request-Id: <uuid ou identificador unico>` opcional, mas recomendado para debug

## Assinatura

A assinatura usa HMAC SHA-256 com secret compartilhado.

String assinada:

```text
<timestamp>.<body-json-bruto>
```

Geracao:

```php
$signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
```

O `rawBody` deve ser exatamente o JSON enviado na requisicao. Nao reformatar o JSON depois de gerar a assinatura.

## Payload de viagem unica

```json
{
  "lote_id": "scraping-20260811-001",
  "viagem": {
    "numero_viagem": "EXT-12345",
    "placa": "ABC1D23",
    "unidade_negocio": "Bugio",
    "cliente": "Cliente X",
    "documento_transporte": "DOC-999",
    "km_rodado": 120.5,
    "km_pago": 118.0,
    "data_competencia": "2026-08-11",
    "data_inicio": "2026-08-11 08:00:00",
    "data_fim": "2026-08-11 12:30:00",
    "total_destinos": 3,
    "possui_pendencia": false,
    "pendencias": [],
    "motorista1": "Nome Motorista",
    "motorista2": null
  }
}
```

## Payload em lote

```json
{
  "lote_id": "scraping-20260811-001",
  "viagens": [
    {
      "numero_viagem": "EXT-12345",
      "placa": "ABC1D23",
      "unidade_negocio": "Bugio",
      "data_competencia": "2026-08-11",
      "data_inicio": "2026-08-11 08:00:00",
      "data_fim": "2026-08-11 12:30:00"
    }
  ]
}
```

## Campos obrigatorios por viagem

- `numero_viagem`: identificador externo unico da viagem.
- `placa` ou `veiculo_id`: preferir `placa`, pois `veiculo_id` e interno deste sistema.
- `unidade_negocio`.
- `data_competencia`: data valida.
- `data_inicio`: data/hora valida.
- `data_fim`: data/hora valida.

## Campos opcionais por viagem

- `cliente`
- `numero_interno`
- `documento_transporte`
- `km_rodado`
- `km_pago`
- `total_destinos`
- `conferido`
- `ignorar`
- `possui_pendencia`
- `pendencias`
- `motorista1`
- `motorista2`

## Resposta de sucesso

```json
{
  "success": true,
  "message": "Payload recebido e enfileirado para processamento.",
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "lote_id": "scraping-20260811-001",
  "total_viagens": 1
}
```

## Respostas de erro antes do processamento

- `401`: assinatura ausente, invalida ou expirada.
- `422`: JSON valido, mas payload fora do contrato.
- `503`: secret da integracao nao configurado no servidor receptor.

## Processamento assincrono e falhas

Depois do `200`, o processamento ocorre em background. Se alguma viagem falhar ao criar ou atualizar, a falha sera acumulada temporariamente no cache com:

- `request_id`
- `lote_id`
- `numero_viagem`
- indice no lote
- mensagem do erro
- payload da viagem

Periodicamente o sistema receptor envia um e-mail consolidado para `dionathan.silva@transmagnabosco.com.br` e limpa o cache quando o envio for concluido.

## Regras de idempotencia

- Se `numero_viagem` nao existir, a viagem sera criada.
- Se `numero_viagem` ja existir e nao estiver conferida, a viagem sera atualizada.
- Se `numero_viagem` ja existir e estiver conferida, ela nao sera alterada.

## Exemplo cURL

```bash
timestamp=$(date +%s)
body='{"lote_id":"scraping-20260811-001","viagem":{"numero_viagem":"EXT-12345","placa":"ABC1D23","unidade_negocio":"Bugio","data_competencia":"2026-08-11","data_inicio":"2026-08-11 08:00:00","data_fim":"2026-08-11 12:30:00"}}'
signature="sha256=$(printf "%s.%s" "$timestamp" "$body" | openssl dgst -sha256 -hmac "$WEBSCRAPER_API_SECRET" -binary | xxd -p -c 256)"

curl -X POST "https://seu-dominio.com/api/integracoes/viagens" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Timestamp: $timestamp" \
  -H "X-Webhook-Signature: $signature" \
  -H "X-Request-Id: 550e8400-e29b-41d4-a716-446655440000" \
  -d "$body"
```
