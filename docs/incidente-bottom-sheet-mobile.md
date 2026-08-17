# Incidente: Bottom Sheet de viagens mobile

## Resultado correto

Ao tocar em uma viagem no painel `/mobile`, o Livewire seleciona o registro e
mantém `sheetOpen` como `true`. O HTML retornado já contém as classes de abertura
do overlay e do Bottom Sheet. O card usa `wire:click`, portanto a abertura não
depende de um evento Alpine disparado antes da atualização do Livewire.

## Erros cometidos e correções

### 1. Entrada Vite isolada para o Bottom Sheet

Foi criada uma entrada independente para `resources/js/mobile/bottom-sheet.js`.
Em produção, o manifest ainda não possuía essa entrada e o `@vite()` lançou:

```text
Unable to locate file in Vite manifest: resources/js/mobile/bottom-sheet.js
```

Correção: o módulo é importado por `resources/js/app.js`, que é a entrada padrão
do projeto. O hook carrega somente `@vite(['resources/js/app.js'])`.

Regra: não criar uma nova entrada Vite para um script pequeno sem garantir que o
pipeline de deploy gere e publique o novo manifest.

### 2. URL de detalhe no painel errado

`App\Filament\Resources\Viagems\ViagemResource::getUrl('view', ...)` chamado
dentro do painel mobile infere o painel atual. Isso tentou gerar a rota inexistente
`filament.mobile.resources.viagems.view`.

Correção:

```php
ViagemResource::getUrl('view', ['record' => $id], panel: 'admin')
```

Regra: ao gerar URL para recurso de outro painel Filament, sempre informar
explicitamente `panel`.

### 3. Abertura perdida durante o re-render do Livewire

O fluxo inicial abria o sheet com um `CustomEvent` Alpine e, em seguida, chamava
`$wire.selecionarViagem()`. O re-render substituía o DOM e reinicializava o estado
Alpine com `open = false`. O sintoma era um escurecimento rápido sem painel visível.

Correção final:

- O card usa `wire:click="selecionarViagem(id)"`.
- `selecionarViagem()` define `selectedViagemId` e `sheetOpen = true`.
- O sheet usa `$wire.entangle('sheetOpen').live` para o estado Alpine.
- O HTML Blade também recebe `is-open` e `is-visible` diretamente de `$sheetOpen`.

Regra: em componentes Livewire que são re-renderizados por uma ação, o estado
essencial de visibilidade deve existir no Livewire. Não usar somente um evento
Alpine anterior ao re-render como fonte de verdade.

### 4. Validação insuficiente

`npm run build`, Pint e compilação de views validam sintaxe e assets, mas não
validam o gesto de tocar no card, a atualização Livewire e a abertura visual.

Regra: antes de declarar esse tipo de fluxo concluído, validar no navegador:

1. Tocar no card abre o sheet e mostra o registro correto.
2. Fechar pelo botão, overlay e Escape funciona.
3. Abrir outro card troca os dados sem fechar indevidamente.
4. A URL de "Abrir registro completo" abre `/admin/viagems/{id}`.

## Publicação em produção

`public/build` é ignorado pelo Git. Alterações em `resources/js/**` exigem que o
artefato Vite seja gerado no deploy ou distribuído pelo CI.

O projeto usa Vite 7 e requer Node `20.19+` ou `22.12+`. O servidor estava em
Node 18, o que também impedia o build por incompatibilidade do Vite e do binding
nativo do Tailwind.

Depois de publicar o código, executar no servidor com Node compatível:

```bash
npm ci
npm run build
php artisan optimize:clear
php artisan view:cache
```

Não atribuir um problema ao deploy sem antes confirmar que o commit com a correção
foi publicado e que o servidor recebeu esse commit.

## PWA

`display: standalone` ou `display_override: fullscreen` só vale para o aplicativo
instalado. Uma aba comum do navegador móvel sempre pode exibir a barra do navegador.
Validar a instalação usando o mesmo domínio HTTPS, o mesmo perfil do navegador e
o mesmo `id` do manifest (`/mobile`).
