<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Relatório de Apontamentos da Oficina</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #111; }
        .box { border: 1px solid #111; padding: 6px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111; padding: 4px; vertical-align: top; }
        th { background: #eee; }
        .no-border td { border: none; }
        .title { font-size: 15px; font-weight: bold; text-align: center; margin-bottom: 8px; }
        .subtitle { font-size: 11px; text-align: center; margin-bottom: 10px; }
        .section-title { font-weight: bold; margin-bottom: 4px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .responsavel-header { width: 100%; margin-bottom: 6px; }
        .responsavel-header td { border: none; padding: 2px 0; }
        .assinatura { height: 18px; border-bottom: 1px solid #111; }
        .assinatura-label { font-size: 8px; text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>

<body>
    <div class="subtitle">
        Período filtrado: {{ $periodoInicio->format('d/m/Y') }} a {{ $periodoFim->format('d/m/Y') }}
        | Total de OS: {{ $ordensServico->count() }}
    </div>

    @foreach ($ordensServico as $ordemServico)
        @include('pdf.partials.oficina-ordem-servico-content', ['ordemServico' => $ordemServico])

        @if (! $loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
