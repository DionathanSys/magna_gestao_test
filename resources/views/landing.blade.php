<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magna Gestão - Sistema de Gestão de Frotas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            width: 100%;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .logo {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 20px;
            margin-bottom: 40px;
            font-weight: 500;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 50px 0;
            text-align: left;
        }

        .feature {
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .feature-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .feature-description {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }

        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 48px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }

        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .card {
                padding: 40px 30px;
            }

            .logo {
                font-size: 36px;
            }

            .subtitle {
                font-size: 16px;
            }

            .features {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .btn-primary {
                padding: 14px 36px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">🚚 Magna Gestão</div>
            <div class="subtitle">Sistema Completo de Gestão de Frotas</div>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon">📊</div>
                    <div class="feature-title">Resultados por Período</div>
                    <div class="feature-description">Acompanhe faturamento, custos e lucros com análises comparativas mensais.</div>
                </div>

                <div class="feature">
                    <div class="feature-icon">⛽</div>
                    <div class="feature-title">Controle de Abastecimento</div>
                    <div class="feature-description">Monitore consumo, desvios de meta e gestão eficiente de combustível.</div>
                </div>

                <div class="feature">
                    <div class="feature-icon">🚛</div>
                    <div class="feature-title">Gestão de Viagens</div>
                    <div class="feature-description">Controle completo de viagens, documentos de frete e dispersão.</div>
                </div>

                <div class="feature">
                    <div class="feature-icon">🔧</div>
                    <div class="feature-title">Manutenção de Veículos</div>
                    <div class="feature-description">Organize ordens de serviço, custos e histórico de manutenções.</div>
                </div>

                <div class="feature">
                    <div class="feature-icon">🔄</div>
                    <div class="feature-title">Pneus e Rodízios</div>
                    <div class="feature-description">Controle de estoque, movimentações e vida útil dos pneus.</div>
                </div>

                <div class="feature">
                    <div class="feature-icon">📈</div>
                    <div class="feature-title">Relatórios Inteligentes</div>
                    <div class="feature-description">Dashboards com KPIs, variações e indicadores de performance.</div>
                </div>
            </div>

            <a href="/admin" class="btn-primary">Acessar Sistema</a>

            <div class="footer">
                © {{ date('Y') }} Magna Gestão - Todos os direitos reservados
            </div>
        </div>
    </div>
</body>
</html>
