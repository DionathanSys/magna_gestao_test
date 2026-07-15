# Ordem de Servico Mobile

## Visao geral

As telas mobile de Ordem de Servico foram implementadas como pages customizadas do Filament, com Blade manual para o layout e Livewire/Filament Schemas para formularios e acoes.

Fluxo principal:

1. Listagem mobile: `app/Filament/Resources/OrdemServicos/Pages/MobileListOrdemServicos.php`
2. Criacao mobile: `app/Filament/Resources/OrdemServicos/Pages/MobileCreateOrdemServico.php`
3. Detalhe/edicao mobile: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php`

Views Blade:

1. `resources/views/filament/resources/ordem-servicos/pages/mobile-list.blade.php`
2. `resources/views/filament/resources/ordem-servicos/pages/mobile-create.blade.php`
3. `resources/views/filament/resources/ordem-servicos/pages/mobile-detail.blade.php`

Rotas registradas no resource:

- `mobile-list` => `/mobile`
- `mobile-create` => `/mobile/create`
- `mobile-detail` => `/mobile/{record}`

Arquivo: `app/Filament/Resources/OrdemServicos/OrdemServicoResource.php:57-66`

## Arquitetura geral

O padrao usado nessas pages e:

1. A classe PHP herda de `Filament\Resources\Pages\Page`
2. O visual e desenhado manualmente na Blade
3. Os campos de formulario usam schemas do Filament
4. As interacoes sao feitas com propriedades/metodos Livewire da propria page
5. A page de detalhe concentra quase toda a operacao mobile da OS

Separacao de responsabilidades:

- PHP da page: carrega registro, monta queries, executa acoes, controla estado dos formularios
- Blade: monta cards, tabs, secoes, botoes, barra fixa inferior
- Schemas: definem campos de OS, item de servico, servico novo e agendamento

## 1. Listagem mobile

### Arquivos

- Classe: `app/Filament/Resources/OrdemServicos/Pages/MobileListOrdemServicos.php`
- Blade: `resources/views/filament/resources/ordem-servicos/pages/mobile-list.blade.php`

### Papel da tela

E a porta de entrada do fluxo mobile. Mostra as OS abertas, permite filtrar por aba e navegar para:

1. Criar uma nova OS
2. Abrir o detalhe/edicao de uma OS existente

### Como a listagem funciona

Estado principal:

- `public string $activeTab = 'pendente';`

Abas disponiveis:

- `hoje`
- `pendente`
- `todas`

Consulta principal:

- `getOrdensServicoProperty()`
- arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileListOrdemServicos.php:25-39`

Regras da query:

1. Carrega `veiculo` e `itens.servico`
2. Exclui OS `CONCLUIDO` e `CANCELADO`
3. Aplica o filtro conforme a aba ativa

Comportamento por aba:

- `hoje`: `whereDate('data_inicio', today())`
- `pendente`: `status = PENDENTE`
- `todas`: todas as OS abertas

Contadores:

- `getHojeCount()`
- `getPendenteCount()`
- `getTodasCount()`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileListOrdemServicos.php:41-67`

### Layout da Blade

Arquivo: `resources/views/filament/resources/ordem-servicos/pages/mobile-list.blade.php`

Estrutura:

```blade
<x-filament-panels::page>
    <style>...</style>

    <div class="os-mobile-summary">
        <div class="os-mobile-hero">...</div>
        <div class="os-mobile-mini-grid">...</div>
    </div>

    <div class="os-mobile-tabs">...</div>

    <div class="os-mobile-list">
        @forelse ($this->ordensServico as $ordemServico)
            <a href="{{ $this->getDetailUrl($ordemServico) }}" class="os-mobile-card">...</a>
        @empty
            <div class="os-mobile-empty">...</div>
        @endforelse
    </div>
</x-filament-panels::page>
```

### Blocos visuais da listagem

1. Hero superior
- titulo: `Ordens Abertas`
- subtitulo operacional
- contador total aberto

2. Coluna lateral superior
- botao `Nova OS`
- mini-card `Pendentes`

3. Barra de tabs
- tres botoes manuais
- troca a prop `activeTab`
- mostra contadores dentro do proprio botao

4. Lista de cards clicaveis
- cada card leva ao detalhe mobile da OS
- titulo: `OS #id - placa`
- subtitulo: tipo de manutencao
- badge de status
- metadata: abertura e quilometragem
- resumo: quantidade de servicos vinculados

### Classes CSS principais

- `.os-mobile-summary`: topo em grid
- `.os-mobile-hero`: card destaque escuro
- `.os-mobile-tabs`: barra de filtros
- `.os-mobile-card`: card clicavel da OS
- `.os-mobile-meta`: grid de dados rapidos

## 2. Criacao mobile

### Arquivos

- Classe: `app/Filament/Resources/OrdemServicos/Pages/MobileCreateOrdemServico.php`
- Blade: `resources/views/filament/resources/ordem-servicos/pages/mobile-create.blade.php`

### Papel da tela

Tela enxuta para abrir uma nova OS no celular. Nao tenta resolver itens, custos e agendamentos aqui. Ela cria a OS minima e redireciona para o detalhe mobile, onde a operacao continua.

### Campos da criacao

Schema da page:

- `OrdemServicoVeiculoInput::make()`
- `OrdemServicoForm::getQuilometragemFormField()`
- `OrdemServicoTipoManutencaoInput::make()`
- `OrdemServicoDataAberturaInput::make()`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileCreateOrdemServico.php:43-62`

Defaults aplicados no `mount()`:

1. `tipo_manutencao = CORRETIVA`
2. `data_inicio = now()`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileCreateOrdemServico.php:35-41`

### Salvamento

Metodo: `salvar()`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileCreateOrdemServico.php:64-84`

Fluxo:

1. Le estado do formulario
2. Busca o veiculo com `kmAtual`
3. Valida que a quilometragem informada nao seja menor que a atual
4. Preenche automaticamente:
   - `created_by`
   - `status = PENDENTE`
   - `status_sankhya = PENDENTE`
5. Cria a `OrdemServico`
6. Redireciona para a page `mobile-detail` da OS criada

### Layout da Blade

Arquivo: `resources/views/filament/resources/ordem-servicos/pages/mobile-create.blade.php`

Estrutura:

```blade
<x-filament-panels::page>
    <form wire:submit="salvar">
        {{ $this->form }}
    </form>

    <div>
        <x-filament::button wire:click="salvar">Criar Ordem de Servico</x-filament::button>
    </div>

    <div>
        <a href="{{ $this->getListUrl() }}">Voltar para lista</a>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
```

Observacao importante:

- essa tela tem layout propositalmente simples
- o foco visual mais forte foi colocado no detalhe mobile, nao na criacao

## 3. Detalhe e edicao mobile

### Arquivos

- Classe: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php`
- Blade: `resources/views/filament/resources/ordem-servicos/pages/mobile-detail.blade.php`

### Papel da tela

Essa e a tela central do fluxo mobile. Ela funciona como:

1. detalhe da OS
2. edicao dos dados da OS
3. manutencao dos itens/servicos da OS
4. criacao, edicao e vinculacao de agendamentos
5. vinculacao de custos de manutencao
6. encerramento ou exclusao da OS

### Estado Livewire principal

Propriedades de controle:

- `data`: estado do formulario principal da OS
- `activeTab`: aba ativa da tela
- `agendamentoBusca`: filtro das pendencias do veiculo
- `showFormServico`: abre/fecha formulario de item
- `showFormNovoServico`: abre/fecha formulario de cadastro de servico
- `showFormAgendamento`: abre/fecha formulario de agendamento
- `formDataServico`
- `formDataNovoServico`
- `formDataAgendamento`
- `editandoItemServicoId`
- `reagendandoItemServicoId`
- `editingAgendamentoId`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:47-70`

### Carregamento do registro

No `mount()`:

1. resolve o registro pela rota
2. carrega relacoes necessarias
3. preenche o formulario principal com os atributos da OS

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:71-75`

Relacoes carregadas em `loadRecordRelations()`:

- `veiculo`
- `itens.servico`
- `itens.comentarios`
- `agendamentos.servico`
- `agendamentos.parceiro`
- `agendamentosPendentes.servico`
- `agendamentosPendentes.parceiro`
- `planoPreventivoVinculado.planoPreventivo`
- `manutencaoLancamentos`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:649-661`

### Formulario principal da OS

Schema principal:

- veiculo
- quilometragem
- tipo manutencao
- status
- status sankhya
- parceiro externo
- data fim

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:77-104`

Salvar dados da OS:

- metodo `salvarForm()`
- apenas faz `update($data)` no registro e recarrega a OS

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:134-140`

## 4. Estrutura visual da page de detalhe

### Estrutura geral da Blade

```blade
<x-filament-panels::page>
    <style>...</style>

    <div class="os-mob-page">
        <div class="os-mob-card">Header</div>

        <div class="os-mob-tab-bar">Tabs</div>

        @if ($activeTab === 'servicos') ... @endif
        @if ($activeTab === 'form') ... @endif
        @if ($activeTab === 'agendamentos') ... @endif
        @if ($activeTab === 'custos') ... @endif

        <x-filament-actions::modals />

        <div class="os-mob-bottom-bar">Acoes fixas</div>
    </div>
</x-filament-panels::page>
```

### Header

Bloco inicial com:

1. numero da OS
2. tipo de manutencao
3. placa do veiculo
4. badge de status
5. botao de voltar para a lista
6. KPIs de cabecalho:
   - KM
   - Abertura
   - Fim, se existir

Blade: `resources/views/filament/resources/ordem-servicos/pages/mobile-detail.blade.php:41-79`

### Tabs internas

A page nao usa tabs nativas do Filament. Ela cria tabs visuais manuais em uma barra sticky:

- `Servicos`
- `Dados`
- `Agend.`
- `Custos`

Blade: `resources/views/filament/resources/ordem-servicos/pages/mobile-detail.blade.php:81-87`

### Barra inferior fixa

Fica presa no rodape com quatro acoes:

1. `Salvar`
2. `PDF`
3. `Encerrar`
4. `Excluir`

Blade: `resources/views/filament/resources/ordem-servicos/pages/mobile-detail.blade.php:359-375`

Metodos usados:

- `salvarForm()`
- `getPdfUrl()`
- `encerrar()`
- `excluirOrdemServico()`

## 5. Aba Servicos

### Objetivo

Gerenciar os itens de servico da OS. Aqui o usuario consegue:

1. cadastrar um servico novo no cadastro mestre
2. vincular um servico existente a OS
3. editar um item ja vinculado
4. excluir um item da OS
5. reagendar um item pendente

### Estrutura Blade da aba

Arquivo: `resources/views/filament/resources/ordem-servicos/pages/mobile-detail.blade.php:89-168`

Blocos:

1. Cabecalho da secao
- total de itens
- botao `Novo Servico`
- botao `Adicionar`

2. Formulario opcional de cadastro de servico
- aparece quando `showFormNovoServico = true`

3. Formulario opcional de item da OS
- aparece quando `showFormServico = true`

4. Lista de itens vinculados
- nome do servico
- codigo, posicao, badge de status
- observacao opcional
- botoes de acao

### Como adicionar novos itens

Existem dois caminhos diferentes.

#### Caminho A: vincular um servico ja existente

Fluxo:

1. tocar em `Adicionar`
2. abre o formulario `formServico`
3. selecionar um servico existente
4. se o servico controlar posicao, preencher `posicao`
5. preencher observacao e status se necessario
6. tocar em `Vincular`

Metodos envolvidos:

- `toggleFormServico()` abre/fecha e inicializa o form
- `salvarServico()` cria ou atualiza o item

Arquivos:

- `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:168-183`
- `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:202-235`

No create do item:

1. le o estado do form
2. injeta `ordem_servico_id = $this->record->id`
3. chama `ItemOrdemServicoService->create($data)`
4. fecha o formulario
5. recarrega o registro

#### Caminho B: cadastrar um servico novo e ja deixar pronto para vincular

Fluxo:

1. tocar em `Novo Servico`
2. abre `formNovoServico`
3. preencher dados do cadastro mestre do servico
4. tocar em `Salvar servico`
5. o sistema cria o servico
6. a tela abre ou mantem aberto o form de item
7. o form de item ja vem preenchido com o `servico_id` novo
8. usuario conclui o vinculo do item na OS

Metodos envolvidos:

- `toggleFormNovoServico()`
- `salvarNovoServico()`

Arquivos:

- `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:185-200`
- `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:237-259`

Esse comportamento e importante: criar um `Servico` no cadastro nao cria automaticamente um `ItemOrdemServico`. Ainda existe um segundo passo de vinculo do item a OS.

### Campos do form de item

Schema: `app/Filament/Resources/OrdemServicos/Schemas/ItemOrdemServicoForm.php`

Campos:

1. `servico_id`
2. `controla_posicao` (toggle somente leitura)
3. `posicao`
4. `status` quando `includeStatus = true`
5. `observacao`

Comportamento importante:

1. `servico_id` usa busca customizada, nao `relationship()`
2. ao selecionar um servico, a tela busca o cadastro e seta `controla_posicao`
3. se nao controlar posicao, limpa `posicao`
4. `posicao` so aparece e so e enviado quando `controla_posicao = true`

Arquivo: `app/Filament/Resources/OrdemServicos/Schemas/ItemOrdemServicoForm.php:58-115`

### Campos do cadastro mestre de servico

Schema: `app/Filament/Resources/Servicos/Schemas/ServicoForm.php`

Campos:

1. `codigo`
2. `descricao`
3. `complemento`
4. `tipo`
5. `controla_posicao`
6. `is_active`

Arquivo: `app/Filament/Resources/Servicos/Schemas/ServicoForm.php:13-70`

### Edicao e exclusao de item

Editar:

1. botao lapis chama `editarServico(id)`
2. carrega o item
3. preenche `formDataServico`
4. abre o mesmo formulario de item em modo edicao
5. `salvarServico()` detecta `editandoItemServicoId` e chama `update`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:435-455`

Excluir:

1. botao lixeira chama `excluirServico(id)`
2. usa `App\Services\OrdemServico\ItemOrdemServicoService->delete($item)`
3. recarrega o registro

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:457-472`

## 6. Aba Dados

### Objetivo

Editar os dados principais da OS.

### Estrutura Blade

```blade
@if ($activeTab === 'form')
    <div class="os-mob-card">
        <form wire:submit="salvarForm">
            {{ $this->form }}
        </form>
    </div>
@endif
```

Arquivo: `resources/views/filament/resources/ordem-servicos/pages/mobile-detail.blade.php:170-177`

### Campos visiveis

- veiculo
- quilometragem
- tipo manutencao
- status
- sankhya
- parceiro externo
- data fim

Schema fonte:

- `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:77-104`
- reaproveita partes de `app/Filament/Resources/OrdemServicos/Schemas/OrdemServicoForm.php`

## 7. Aba Agendamentos

### Objetivo

Gerenciar a relacao da OS com agendamentos.

A aba foi dividida em tres blocos:

1. agendamentos desta OS
2. outras pendencias do veiculo
3. planos preventivos vinculados

### Bloco 1: Agendamentos desta OS

Permite:

1. criar novo agendamento
2. editar agendamento pendente
3. reagendar um item da OS transformando-o em agendamento

Acoes principais:

- `abrirNovoAgendamento()`
- `editarAgendamento(int $agendamentoId)`
- `salvarAgendamento()`
- `fecharFormAgendamento()`

Arquivos:

- `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:261-323`
- `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:325-433`

Regras importantes de `salvarAgendamento()`:

1. se `editingAgendamentoId` estiver preenchido, edita um agendamento pendente existente
2. se `reagendandoItemServicoId` estiver preenchido:
   - cria um novo agendamento com categoria `REAGENDAMENTO`
   - muda o item original da OS para status `ADIADO`
3. caso contrario, cria um novo agendamento normal via `AgendamentoService->create($data)`

### Bloco 2: Outras pendencias do veiculo

Mostra os agendamentos pendentes do mesmo veiculo ainda nao vinculados a OS.

Permite:

1. buscar por texto
2. vincular agendamento a OS
3. editar agendamento
4. cancelar agendamento

Collection usada:

- `getAgendamentosVeiculoProperty()`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:577-602`

Filtro textual procura em:

- descricao do servico
- parceiro
- observacao
- categoria

### Bloco 3: Planos preventivos

E apenas um bloco de leitura com os planos preventivos vinculados a OS.

Blade: `resources/views/filament/resources/ordem-servicos/pages/mobile-detail.blade.php:273-290`

## 8. Aba Custos

### Objetivo

Gerenciar os lancamentos de manutencao ligados a OS.

Blocos:

1. `Custos Vinculados`
2. `Custos Pendentes do Veiculo`

### Custos vinculados

Mostra para cada lancamento:

- produto
- data
- origem
- parceiro
- tipo de vinculo
- valor
- acao de desvincular

Metodo de acao:

- `desvincularLancamento(int $lancamentoId)`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:542-558`

### Custos pendentes

Busca ate 15 lancamentos do mesmo veiculo ainda sem `ordem_servico_id`.

Collection:

- `getLancamentosPendentesProperty()`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:615-624`

Vinculo manual:

- `vincularLancamento(int $lancamentoId)`
- usa `ManutencaoLancamentoVinculoService`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:524-540`

## 9. Acoes globais da OS

### Encerrar OS

Metodo: `encerrar()`

1. chama `OrdemServicoService->encerrarOrdemServico($this->record)`
2. se der erro, notifica
3. se sucesso, recarrega o registro

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:142-155`

### Excluir OS

Metodo: `excluirOrdemServico()`

1. faz `delete()` no registro
2. notifica sucesso
3. redireciona para a lista mobile

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:157-164`

### PDF

Metodo helper:

- `getPdfUrl()`
- retorna `route('ordem-servico.pdf.visualizar', $this->record->id)`

Arquivo: `app/Filament/Resources/OrdemServicos/Pages/MobileDetailOrdemServico.php:562-565`

## 10. Estrutura dos arquivos Blade

### `mobile-list.blade.php`

Responsabilidade:

- desenhar cards de listagem e navegacao para criar/detalhar

Estrutura:

1. `<style>` inline da tela
2. resumo superior com hero + CTA + mini-card
3. tabs manuais
4. `@forelse` de OS em cards clicaveis

### `mobile-create.blade.php`

Responsabilidade:

- desenhar o formulario minimo de abertura da OS

Estrutura:

1. formulario Filament `{{ $this->form }}`
2. botao grande `Criar Ordem de Servico`
3. link de voltar para lista
4. `<x-filament-actions::modals />`

### `mobile-detail.blade.php`

Responsabilidade:

- desenhar toda a experiencia operacional mobile da OS

Estrutura:

1. `<style>` inline extenso da page
2. header da OS com KPIs
3. barra sticky de tabs
4. secao condicional `servicos`
5. secao condicional `form`
6. secao condicional `agendamentos`
7. secao condicional `custos`
8. modais Filament
9. bottom bar fixa com acoes criticas

## 11. Resumo pratico para replicar em outro sistema

Se o objetivo for portar o layout/design e tambem o comportamento, a composicao real e:

1. Uma listagem mobile com cards clicaveis e filtros simples
2. Uma tela minima de criacao de OS
3. Uma tela unica de detalhe/edicao com tabs manuais
4. Na aba `Servicos`, dois fluxos distintos:
   - cadastrar novo servico no cadastro mestre
   - vincular servico existente como item da OS
5. Na aba `Agendamentos`, tres fluxos:
   - criar/agendar
   - vincular pendencia do veiculo
   - reagendar item pendente da OS
6. Na aba `Custos`, dois fluxos:
   - vincular custo pendente do veiculo
   - desvincular custo ja associado
7. Uma barra fixa inferior com acoes de persistencia e encerramento

## 12. Observacoes importantes de implementacao

1. A maior parte do layout esta em CSS inline nas Blades, nao em componentes reutilizaveis de design system.
2. As tabs visuais sao manuais, controladas por `activeTab`, nao por um componente pronto do Filament.
3. A page de detalhe concentra muita responsabilidade operacional; se for portar para outro sistema, vale decidir se esse comportamento continua em uma unica tela ou se sera quebrado.
4. O fluxo de adicionar item e um fluxo em dois niveis:
   - `Servico` e o cadastro mestre
   - `ItemOrdemServico` e o vinculo do servico dentro da OS
5. Reagendamento nao apenas cria agendamento: ele tambem altera o status do item original para `ADIADO`.
