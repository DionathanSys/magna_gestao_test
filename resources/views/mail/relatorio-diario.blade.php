<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório Diário</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; }
        .data-item { margin: 10px 0; padding: 10px; background-color: #e9ecef; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Relatório Diário</h1>
            <p>Data: {{ $dados['data'] }}</p>
        </div>

        <div class="data-item">
            <strong>Total de Usuários:</strong> {{ $dados['total_usuarios'] }}
        </div>

        <div class="data-item">
            <strong>Ordens de Serviço Hoje:</strong> {{ $dados['total_ordens'] }}
        </div>

        <p>Este é seu relatório diário automatizado.</p>
    </div>
</body>
</html>
