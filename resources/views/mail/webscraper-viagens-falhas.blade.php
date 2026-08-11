<x-mail::message>
# Falhas na integracao WebScraper de viagens

Foram encontradas {{ count($errors) }} falha(s) acumuladas ate {{ $generatedAt }}.

@foreach ($errors as $error)
## Viagem {{ $error['numero_viagem'] ?? 'N/A' }}

- Lote: `{{ $error['lote_id'] ?? 'N/A' }}`
- Request ID: `{{ $error['request_id'] ?? 'N/A' }}`
- Indice no lote: `{{ $error['index'] ?? 'N/A' }}`
- Capturado em: `{{ $error['capturado_em'] ?? 'N/A' }}`
- Erro: {{ $error['erro'] ?? 'N/A' }}

<x-mail::panel>
```json
@json($error['payload'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
```
</x-mail::panel>
@endforeach

</x-mail::message>
