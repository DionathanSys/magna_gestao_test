# Exemplo: Adicionar Documentos de Frete a uma Viagem Existente

## Descrição
Este documento mostra como adicionar novos documentos de frete a uma viagem existente e recalcular automaticamente o `km_pago`.

## 🎯 Via Interface (Filament)

### Vincular Documento de Frete a uma Viagem

Na tela de listagem de Documentos de Frete (`DocumentoFretesTable`), existe uma action diretamente no registro:

1. **Action "Vincular Viagem"** (ícone de link verde)
   - Aparece apenas em documentos que ainda **não possuem viagem vinculada**
   - Ao clicar, abre um modal solicitando o **ID da Viagem**
   - Digite o ID da viagem e confirme

**O que acontece automaticamente:**
- ✅ O campo `viagem_id` do documento é atualizado
- ✅ O campo `documento_transporte` do documento recebe o valor da viagem
- ✅ O `km_pago` da viagem é **recalculado automaticamente**
- ✅ Exibe notificação de sucesso com o novo valor de km_pago

**Localização:** `app/Filament/Resources/DocumentoFretes/Tables/DocumentoFretesTable.php`

---

## 💻 Uso Programático

### 1. Adicionar Documentos a uma Viagem

```php
use App\Models\Viagem;
use App\Services\DocumentoFrete\Actions\GerarViagemNutrepampaFromDocumento;

// Buscar a viagem existente
$viagem = Viagem::find($viagemId);

// IDs dos novos documentos de frete que serão adicionados
$novosDocumentosIds = [123, 456, 789];

// Criar instância da classe (não precisa passar documentos no construtor para este uso)
$gerarViagem = new GerarViagemNutrepampaFromDocumento(collect());

// Adicionar os documentos e recalcular km_pago automaticamente
$sucesso = $gerarViagem->adicionarDocumentosViagem($viagem, $novosDocumentosIds);

if ($sucesso) {
    // Viagem atualizada com sucesso
    // km_pago foi recalculado automaticamente
    echo "KM Pago atualizado: " . $viagem->fresh()->km_pago;
} else {
    // Erro ao adicionar documentos
    echo "Erro ao adicionar documentos";
}
```

### 2. Recalcular km_pago de uma Viagem

Se você precisar apenas recalcular o `km_pago` baseado nos documentos já vinculados (sem adicionar novos):

```php
use App\Models\Viagem;
use App\Services\DocumentoFrete\Actions\GerarViagemNutrepampaFromDocumento;

// Buscar a viagem
$viagem = Viagem::find($viagemId);

// Criar instância
$gerarViagem = new GerarViagemNutrepampaFromDocumento(collect());

// Recalcular km_pago
$sucesso = $gerarViagem->recalcularKmPagoViagem($viagem);

if ($sucesso) {
    echo "KM Pago recalculado: " . $viagem->fresh()->km_pago;
}
```

## Exemplo em um Controller ou Action

```php
namespace App\Filament\Resources\Viagems\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Viagem;
use App\Services\DocumentoFrete\Actions\GerarViagemNutrepampaFromDocumento;

class AdicionarDocumentosViagemAction
{
    public static function make(): Action
    {
        return Action::make('adicionar_documentos')
            ->label('Adicionar Documentos')
            ->icon('heroicon-o-document-plus')
            ->form([
                Select::make('documentos_ids')
                    ->label('Documentos de Frete')
                    ->multiple()
                    ->searchable()
                    ->options(function (Viagem $record) {
                        // Buscar documentos disponíveis (sem viagem ou da mesma data)
                        return \App\Models\DocumentoFrete::query()
                            ->whereNull('viagem_id')
                            ->where('veiculo_id', $record->veiculo_id)
                            ->pluck('numero_documento', 'id');
                    })
                    ->required(),
            ])
            ->action(function (Viagem $record, array $data) {
                $gerarViagem = new GerarViagemNutrepampaFromDocumento(collect());
                
                $sucesso = $gerarViagem->adicionarDocumentosViagem(
                    $record, 
                    $data['documentos_ids']
                );
                
                if ($sucesso) {
                    Notification::make()
                        ->success()
                        ->title('Documentos adicionados com sucesso!')
                        ->body("KM Pago recalculado: {$record->fresh()->km_pago}")
                        ->send();
                } else {
                    Notification::make()
                        ->danger()
                        ->title('Erro ao adicionar documentos')
                        ->send();
                }
            });
    }
}
```

---

## 🔄 Fluxo de Trabalho Completo

### Cenário 1: Vincular um único documento via interface
1. Acesse a listagem de **Documentos de Frete**
2. Localize o documento sem viagem vinculada
3. Clique no ícone de link verde (**Vincular Viagem**)
4. Digite o **ID da Viagem**
5. Confirme
6. ✅ Documento vinculado e km_pago recalculado automaticamente

### Cenário 2: Vincular múltiplos documentos via código
```php
use App\Models\Viagem;
use App\Services\DocumentoFrete\Actions\GerarViagemNutrepampaFromDocumento;

$viagem = Viagem::find(123);
$documentosIds = [456, 789, 101];

$gerarViagem = new GerarViagemNutrepampaFromDocumento(collect());
$sucesso = $gerarViagem->adicionarDocumentosViagem($viagem, $documentosIds);
```

### Cenário 3: Apenas recalcular km_pago
```php
use App\Models\Viagem;
use App\Services\DocumentoFrete\Actions\GerarViagemNutrepampaFromDocumento;

$viagem = Viagem::find(123);

$gerarViagem = new GerarViagemNutrepampaFromDocumento(collect());
$sucesso = $gerarViagem->recalcularKmPagoViagem($viagem);
```

---

## O que acontece automaticamente

Quando você adiciona documentos a uma viagem:

1. ✅ Os novos documentos são buscados no banco de dados
2. ✅ O valor total atual da viagem é calculado (documentos existentes)
3. ✅ O valor dos novos documentos é somado
4. ✅ O `km_pago` é **recalculado automaticamente** usando as faixas de preço:
   - Até 120 km: R$ 5,65/km
   - Até 500 km: R$ 5,21/km
   - Até 1000 km: R$ 4,73/km
   - Acima de 1000 km: R$ 4,58/km
5. ✅ A viagem é atualizada com o novo `km_pago` e `valor_total_documento`
6. ✅ Os documentos são vinculados à viagem
7. ✅ Logs detalhados são gerados

## Logs Gerados

Os métodos geram logs detalhados incluindo:
- ID da viagem
- IDs dos documentos adicionados
- Valor total anterior
- Valor total dos novos documentos
- Novo valor total
- KM pago anterior
- KM pago novo
- Faixa de cálculo utilizada

Você pode verificar os logs em: `storage/logs/laravel.log`
