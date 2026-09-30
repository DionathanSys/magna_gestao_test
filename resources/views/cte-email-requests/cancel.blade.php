<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cancelar solicitacao de CTe</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; color: #172033; }
        main { max-width: 34rem; margin: 0 auto; }
        button { background: #b42318; border: 0; border-radius: 6px; color: white; cursor: pointer; padding: .75rem 1rem; }
        .card { border: 1px solid #d0d5dd; border-radius: 8px; padding: 1.25rem; }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>Cancelar solicitacao de CTe</h1>
        <p>Viagem: {{ $cteRequest->viagem?->numero_viagem ?? 'N/A' }}</p>
        <p>Documento de transporte: {{ $cteRequest->documento_transporte }}</p>
        @if ($cteRequest->status === 'pending_send')
            <p>A solicitacao ainda nao foi enviada. Deseja abortar o envio?</p>
            <form method="POST" action="{{ request()->fullUrl() }}">
                @csrf
                <button type="submit">Abortar solicitacao</button>
            </form>
        @else
            <p>Esta solicitacao nao esta mais pendente de envio. Status atual: {{ $cteRequest->status }}.</p>
        @endif
    </div>
</main>
</body>
</html>
