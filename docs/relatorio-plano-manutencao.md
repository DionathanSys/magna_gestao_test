# Relatório de Plano de Manutenção Preventiva

## Descrição

Este módulo gera um relatório em PDF com informações detalhadas sobre os planos de manutenção preventiva dos veículos, incluindo:

- Informações do veículo (placa)
- Dados do plano preventivo (descrição e periodicidade)
- Quilometragem da última execução
- Data da última execução
- Quilometragem restante até a próxima manutenção
- Data prevista da próxima manutenção (calculada com base no km médio diário)

## Arquivos Criados/Modificados

### Novos Arquivos

1. **app/Filament/Pages/RelatorioPlanoManutencao.php**
   - Página Filament com formulário de filtros
   - Botões para gerar/visualizar PDF

2. **app/Services/PlanoManutencao/RelatorioPlanoManutencaoService.php**
   - Service responsável por buscar dados e gerar PDF
   - Aplicação de filtros e ordenação

3. **resources/views/filament/pages/relatorio-plano-manutencao.blade.php**
   - View da página Filament com os controles

4. **resources/views/pdf/relatorio-plano-manutencao.blade.php**
   - Template do PDF em formato landscape
   - Layout responsivo com cores para status

### Arquivos Modificados

1. **app/Models/Veiculo.php**
   - Adicionado método `calcularKmMedioDiario(int $dias = 30): float`
   - Adicionado método `calcularDataPrevista(float $kmRestante): ?\Carbon\Carbon`

2. **app/Models/PlanoManutencaoVeiculo.php**
   - Corrigido relacionamento `ultimaExecucao()` para usar `latestOfMany()`
   - Ajustados accessors `proximaExecucao()` e `quilometragemRestante()`

## Como Usar

### Acesso ao Relatório

1. Acesse o painel administrativo Filament
2. Navegue até **Relatórios > Relatório Plano Manutenção**

### Filtros Disponíveis

1. **Veículo** (opcional)
   - Selecione um veículo específico ou deixe vazio para todos ativos

2. **Plano Preventivo** (opcional)
   - Selecione um plano específico ou deixe vazio para todos ativos

3. **KM Restante Máximo** (opcional)
   - Define o limite máximo de km restante
   - Útil para ver apenas manutenções próximas do vencimento
   - Exemplo: 5000 (mostrará apenas veículos com até 5.000 km até a próxima manutenção)

### Ações

- **Baixar PDF**: Faz download do relatório em formato PDF
- **Visualizar PDF**: Abre o PDF no navegador para visualização

## Funcionalidades

### Cálculo de KM Médio Diário

O sistema calcula automaticamente a quilometragem média diária do veículo com base nos últimos 30 dias (configurável) do histórico de quilometragem. Este valor é usado para estimar a data prevista da próxima manutenção.

### Data Prevista

A data prevista da próxima manutenção é calculada dividindo o km restante pelo km médio diário:

```
data_prevista = data_atual + (km_restante / km_medio_diario)
```

Se não houver dados suficientes para calcular o km médio, o campo aparecerá como "N/D" (Não Disponível).

### Cores de Status

O relatório usa cores para indicar a urgência da manutenção:

- **Verde**: KM restante > 1000 km (situação normal)
- **Amarelo**: KM restante ≤ 1000 km (atenção)
- **Vermelho**: KM restante ≤ 0 km (vencido)

### Ordenação

Os dados são automaticamente ordenados por:
1. Placa do veículo (ordem alfabética)
2. KM restante (menor para maior)

## Estrutura do PDF

O relatório em PDF é gerado em formato **landscape (paisagem)** e contém:

- **Cabeçalho**: Título e data/hora de geração
- **Informações**: Total de registros e filtros aplicados
- **Tabela de Dados**: Com as seguintes colunas:
  - Placa
  - Plano Preventivo
  - Periodicidade (km)
  - KM Atual
  - KM Última Execução
  - Data Última Execução
  - Próxima Execução (km)
  - KM Restante
  - KM Médio Diário
  - Data Prevista Próxima Exec.

## Requisitos

- Laravel com Filament
- Pacote `barryvdh/laravel-dompdf` (já instalado)
- Modelos: `Veiculo`, `PlanoPreventivo`, `PlanoManutencaoVeiculo`, `PlanoManutencaoOrdemServico`, `HistoricoQuilometragem`

## Observações

- Veículos sem histórico de quilometragem suficiente terão o km médio como 0 e data prevista como "N/D"
- Apenas veículos ativos são considerados quando não há filtro de veículo específico
- Apenas planos preventivos ativos são considerados quando não há filtro de plano específico
- A última execução é obtida do relacionamento com `PlanoManutencaoOrdemServico`
