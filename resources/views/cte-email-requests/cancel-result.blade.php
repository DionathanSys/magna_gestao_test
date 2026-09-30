<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitacao de CTe</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; color: #172033; }
        main { max-width: 34rem; margin: 0 auto; }
        .card { border: 1px solid #d0d5dd; border-radius: 8px; padding: 1.25rem; }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>{{ $cancelled ? 'Solicitacao abortada' : 'Solicitacao nao abortada' }}</h1>
        @if ($cancelled)
            <p>O envio do email de solicitacao de CTe foi cancelado.</p>
        @else
            <p>A solicitacao ja havia sido processada ou cancelada. Status atual: {{ $cteRequest->status }}.</p>
        @endif
    </div>
</main>
</body>
</html>
