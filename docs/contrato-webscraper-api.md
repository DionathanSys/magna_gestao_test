# Contrato API WebScraper

Este documento descreve as chamadas que a aplicacao de WebScraping deve fazer para enviar dados ao sistema Magnabosco.

## Base URL

Substituir pelo dominio do ambiente receptor:

```text
https://seu-dominio.com
```

## Autenticacao por assinatura

Todas as chamadas devem enviar assinatura HMAC SHA-256.

### Headers obrigatorios

```http
Content-Type: application/json
X-Webhook-Timestamp: <unix timestamp em segundos>
X-Webhook-Signature: sha256=<assinatura>
```

Header recomendado para debug:

```http
X-Request-Id: <uuid ou identificador unico da requisicao>
```

### Como gerar a assinatura

A string assinada deve ser:

```text
<timestamp>.<body-json-bruto>
```

Exemplo em PHP:

```php
$timestamp = time();
$rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
```

Importante: o `rawBody` usado para gerar a assinatura deve ser exatamente o mesmo JSON enviado na requisicao.

## 1. Enviar viagens para criacao/atualizacao

Endpoint:

```http
POST /api/integracoes/viagens
```

Quando a assinatura e o payload forem validos, a API retorna `200` e enfileira o processamento. A criacao/atualizacao acontece em background.

### Payload de viagem unica

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

### Payload em lote

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

### Campos obrigatorios por viagem

- `numero_viagem`: identificador unico da viagem no sistema de origem.
- `placa` ou `veiculo_id`: preferir `placa`.
- `unidade_negocio`.
- `data_competencia`.
- `data_inicio`.
- `data_fim`.

### Campos opcionais por viagem

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

### Resposta de sucesso

```json
{
  "success": true,
  "message": "Payload recebido e enfileirado para processamento.",
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "lote_id": "scraping-20260811-001",
  "total_viagens": 1
}
```

### Regras de processamento

- Se `numero_viagem` nao existir, a viagem sera criada.
- Se `numero_viagem` existir e nao estiver conferida, a viagem sera atualizada.
- Se `numero_viagem` existir e estiver conferida, ela nao sera alterada.
- Falhas no processamento assincrono sao agrupadas e notificadas por e-mail pelo sistema receptor.

## 2. Registrar viagem atual do caminhao

Endpoint:

```http
POST /api/integracoes/viagem-atual
```

Este endpoint registra o estado atual do veiculo para dashboard operacional. O dado nao e permanente; ele fica em cache e e sobrescrito a cada chamada do mesmo veiculo.

### Payload

```json
{
  "veiculo": "ABC1D23",
  "nro_viagem": "EXT-12345",
  "destino": "Chapeco/SC",
  "km_pago": 118.0,
  "inicio": "2026-08-11 08:00:00",
  "status": "em_rota"
}
```

### Aliases aceitos

- `placa` no lugar de `veiculo`.
- `numero_viagem` no lugar de `nro_viagem`.
- `veiculo_id`, caso o sistema de origem conheca o ID interno, mas nao e recomendado.

### Campos obrigatorios

- `veiculo`, `placa` ou `veiculo_id`.
- `nro_viagem` ou `numero_viagem`.
- `destino`.
- `km_pago`.
- `inicio`.
- `status`.

Observacao: o km de cadastro do integrado nao deve ser enviado. O sistema receptor busca esse valor na tabela `integrados`, usando o `destino` recebido para localizar o integrado e apresentar o `km_rota` no dashboard.

### Resposta de sucesso

```json
{
  "success": true,
  "message": "Viagem atual registrada.",
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "veiculo_key": "placa:ABC1D23"
}
```

## Respostas de erro comuns

### Assinatura invalida

Status: `401`

```json
{
  "message": "Invalid signature."
}
```

### Payload invalido

Status: `422`

```json
{
  "success": false,
  "message": "Payload invalido.",
  "errors": {
    "viagem.numero_viagem": [
      "The viagem.numero viagem field is required."
    ]
  }
}
```

### Integracao nao configurada no receptor

Status: `503`

```json
{
  "message": "Integration not configured."
}
```

## Exemplo cURL - viagens

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

## Exemplo cURL - viagem atual

```bash
timestamp=$(date +%s)
body='{"veiculo":"ABC1D23","nro_viagem":"EXT-12345","destino":"Chapeco/SC","km_pago":118,"inicio":"2026-08-11 08:00:00","status":"em_rota"}'
signature="sha256=$(printf "%s.%s" "$timestamp" "$body" | openssl dgst -sha256 -hmac "$WEBSCRAPER_API_SECRET" -binary | xxd -p -c 256)"

curl -X POST "https://seu-dominio.com/api/integracoes/viagem-atual" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Timestamp: $timestamp" \
  -H "X-Webhook-Signature: $signature" \
  -H "X-Request-Id: 550e8400-e29b-41d4-a716-446655440000" \
  -d "$body"
```
