# Instrucao: endpoint para Movimento Diario (Sascar)

Este documento instrui a **aplicacao receptor (sistema Magnabosco)** a criar um ponto de API para receber os dados do relatorio "Movimento Diario" extraido do portal Sascar Telemetria.

O scraper (aplicacao WebScraping) le o relatorio por veiculo e envia os dados nesta API.

## 1. Origem dos dados

Portal: https://telemetria.sascar.com.br/telemetria/pages/controller.jsf
Relatorio: "Daily movement" (Movimento Diario), perido = Hoje, filial de veiculos = Chapeco.

O relatorio e gerado **por veiculo** (uma geracao por placa). Cada relatorio traz:

- Uma linha por dia, com a distancia total e o tempo de movimento do dia.
- Para cada hora do dia (0h a 23h), **6 quadrados** de status.
- Legenda do relatorio (3 status possiveis):

| Codigo | Cor/Icone no relatorio | Significado |
|--------|------------------------|-------------|
| `0`    | `vei_mov.gif`          | Em movimento |
| `1`    | `vei_par_lig.gif`      | Parado com motor ligado |
| `2`    | `vei_des.gif`          | Veiculo desligado |

Na pagina, cada quadrado e um elemento `<div class="minuto_0|1|2">` dentro da coluna da hora. O primeiro quadrado da hora equivale aos primeiros 10 minutos, e assim por diante (6 quadrados = 60 minutos).

## 2. Base URL

Substituir pelo dominio do ambiente receptor:

```text
https://seu-dominio.com
```

## 3. Autenticacao por assinatura

Mesma regra dos demais endpoints do contrato: HMAC SHA-256.

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

```text
<timestamp>.<body-json-bruto>
```

Exemplo em PHP:

```php
$timestamp = time();
$rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
```

O `rawBody` usado na assinatura deve ser exatamente o mesmo JSON enviado no corpo da requisicao.

## 4. Endpoint

```http
POST /api/integracoes/movimento-diario
```

Enviar **uma requisicao por veiculo** (uma placa por chamada). Quando a assinatura e o payload forem validos, a API retorna `200` e enfileira o processamento.

### Payload

```json
{
  "lote_id": "sascar-movimento-diario-20260811-001",
  "veiculo": "RLE7C85",
  "filial": "MAGNABOSCO - CHAPECÓ",
  "dia": "2026-08-11",
  "km": 213.7,
  "tempo_movimento": "10:06:59",
  "horas": [
    { "hora": 0,  "minutos": ["0", "0", "1", "0", "0", "0"] },
    { "hora": 1,  "minutos": ["0", "0", "0", "1", "1", "1"] },
    { "hora": 2,  "minutos": ["1", "1", "1", "2", "2", "2"] },
    { "hora": 3,  "minutos": ["2", "1", "1", "1", "1", "1"] },
    { "hora": 4,  "minutos": ["1", "0", "0", "2", "0", "2"] },
    { "hora": 5,  "minutos": ["0", "0", "1", "1", "1", "2"] },
    { "hora": 6,  "minutos": ["2", "2", "2", "2", "2", "2"] },
    { "hora": 7,  "minutos": ["1", "1", "1", "1", "1", "1"] },
    { "hora": 8,  "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 9,  "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 10, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 11, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 12, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 13, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 14, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 15, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 16, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 17, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 18, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 19, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 20, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 21, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 22, "minutos": ["0", "0", "0", "0", "0", "0"] },
    { "hora": 23, "minutos": ["0", "0", "0", "0", "0", "0"] }
  ]
}
```

### Significado dos campos

| Campo | Tipo | Obrigatorio | Descricao |
|-------|------|-------------|-----------|
| `lote_id` | string | sim | Identificador do lote de scraping (data/hora da extracao). |
| `veiculo` | string | sim | Placa do veiculo. |
| `filial` | string | sim | Filial de veiculos selecionada no relatorio. |
| `dia` | string | sim | Data do relatorio no formato `YYYY-MM-DD`. |
| `km` | float | sim | Distancia total percorrida no dia (coluna Km do relatorio). |
| `tempo_movimento` | string | sim | Tempo total em movimento no dia no formato `HH:MM:SS` (coluna Tempo do relatorio). |
| `horas` | array | sim | 24 itens, um por hora (0 a 23). |
| `horas[].hora` | int | sim | Hora do dia (0-23). |
| `horas[].minutos` | array | sim | 6 valores de status, um por quadrado (cada um representa 10 minutos da hora). Valores validos: `0`, `1` ou `2`. |

### Aliases aceitos

- `placa` no lugar de `veiculo`.
- `data` no lugar de `dia`.
- `tempo` no lugar de `tempo_movimento`.
- `veiculo_id`, caso o scraper repasse o ID interno da Sascar (recomendado apenas como referencia, nunca como identificador unico).

### Codigo de status

| Valor | Status |
|-------|--------|
| `0` | Em movimento |
| `1` | Parado com motor ligado |
| `2` | Veiculo desligado |

### Regras de processamento sugeridas

- Usar a chave `(veiculo, dia)` como identificador unico do registro.
- Se `(veiculo, dia)` ja existir, o registro deve ser **substituido** (a extracao refaz o dia inteiro a cada execucao).
- Validar que `horas` tenha exatamente 24 itens e que cada `minutos` tenha exatamente 6 valores dentro de `0|1|2`; caso contrario, rejeitar com `422`.
- Falhas no processamento assincrono devem ser agrupadas e notificadas por e-mail pelo sistema receptor.

## 5. Resposta de sucesso

Status: `200`

```json
{
  "success": true,
  "message": "Movimento diario recebido e enfileirado para processamento.",
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "lote_id": "sascar-movimento-diario-20260811-001",
  "veiculo_key": "placa:RLE7C85"
}
```

## 6. Respostas de erro comuns

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
    "horas.0.minutos": [
      "The horas.0.minutos must contain exactly 6 items."
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

## 7. Exemplo cURL

```bash
timestamp=$(date +%s)
body='{"lote_id":"sascar-movimento-diario-20260811-001","veiculo":"RLE7C85","filial":"MAGNABOSCO - CHAPECÓ","dia":"2026-08-11","km":213.7,"tempo_movimento":"10:06:59","horas":[{"hora":0,"minutos":["0","0","1","0","0","0"]},{"hora":1,"minutos":["0","0","0","1","1","1"]}]}'
signature="sha256=$(printf "%s.%s" "$timestamp" "$body" | openssl dgst -sha256 -hmac "$WEBSCRAPER_API_SECRET" -binary | xxd -p -c 256)"

curl -X POST "https://seu-dominio.com/api/integracoes/movimento-diario" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Timestamp: $timestamp" \
  -H "X-Webhook-Signature: $signature" \
  -H "X-Request-Id: 550e8400-e29b-41d4-a716-446655440000" \
  -d "$body"
```

## 8. Notas para o scraper (apenas contexto, nao e contrato)

- O relatorio e aberto em uma janela popup ("Pop-up" em Forma de Visualizacao).
- A tabela tem o cabecalho: `Date | Km | Time | 0 | 01 | 02 | ... | 23`.
- Cada coluna de hora contem 6 elementos `<div class="minuto_0|1|2">`.
- E necessario gerar um relatorio por veiculo da filial Chapeco (lista do campo Veiculo apos selecionar a filial).
