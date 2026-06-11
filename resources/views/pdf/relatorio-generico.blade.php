<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px; line-height: 1.3; color: #333; padding: 15px;
        }
        .header {
            text-align: center; margin-bottom: 15px;
            border-bottom: 2px solid #0066cc; padding-bottom: 8px;
        }
        .header h1 { color: #0066cc; font-size: 16px; margin-bottom: 3px; }
        .header p { color: #666; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th {
            background-color: #0066cc; color: white; padding: 5px 4px;
            text-align: left; font-size: 8px; font-weight: bold; border: 1px solid #0055aa;
        }
        td { padding: 4px; border: 1px solid #ddd; font-size: 8px; word-wrap: break-word; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer {
            margin-top: 15px; text-align: center; font-size: 8px;
            color: #666; border-top: 1px solid #ddd; padding-top: 8px;
        }
        .sem-dados { text-align: center; padding: 30px; color: #999; font-size: 11px; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $titulo }}</h1>
    <p>Gerado em: {{ $dataGeracao }}</p>
</div>

@if(count($linhas) > 0)
    <table>
        <thead>
            <tr>
                @foreach($colunas as $coluna)
                    <th style="width: {{ $coluna['width'] ?? 'auto' }}; {{ ($coluna['align'] ?? 'left') === 'center' ? 'text-align: center;' : (($coluna['align'] ?? 'left') === 'right' ? 'text-align: right;' : '') }}">
                        {{ $coluna['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($linhas as $linha)
                <tr>
                    @foreach($colunas as $coluna)
                        <td class="{{ $coluna['align'] ?? '' === 'center' ? 'text-center' : ($coluna['align'] ?? '' === 'right' ? 'text-right' : '') }}">
                            {!! $linha[$coluna['key']] ?? '-' !!}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="sem-dados">
        <p><strong>Nenhum registro encontrado.</strong></p>
    </div>
@endif

<div class="footer">
    <p>Sistema de Gestao - Magna | Relatorio gerado automaticamente | {{ $dataGeracao }}</p>
</div>
</body>
</html>
