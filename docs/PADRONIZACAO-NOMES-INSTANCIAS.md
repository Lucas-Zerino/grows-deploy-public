# Padronização de Nomes de Instâncias

## Problema

Empresas diferentes queriam criar canais (instâncias) com o mesmo nome, mas como o nome é usado como ID único nos providers (WAHA/UAZAPI), isso causava conflitos e impedía que múltiplas empresas usassem o mesmo nome.

## Solução Implementada

Foi implementada uma padronização onde:

1. **No banco de dados (GrowHub)**: O nome é salvo exatamente como enviado pelo usuário
2. **Nos providers (WAHA/UAZAPI)**: O nome é criado no formato `{company_id}-{nome}`

### Exemplos

Se a empresa com ID `123` criar uma instância chamada "Vendas":
- **Nome salvo no banco**: `Vendas`
- **Nome criado na WAHA/UAZAPI**: `123-Vendas`

Se a empresa com ID `456` criar uma instância com o mesmo nome "Vendas":
- **Nome salvo no banco**: `Vendas`
- **Nome criado na WAHA/UAZAPI**: `456-Vendas`

Dessa forma, não há conflito pois os nomes nos providers são únicos (`123-Vendas` e `456-Vendas`), mas cada empresa vê apenas o nome simples "Vendas".

## Arquivos Modificados

### 1. `src/Controllers/InstanceController.php`

**Linha 75-79**: Ao criar uma instância, o nome é formatado com o company_id antes de enviar ao provider:

```php
// IMPORTANTE: O nome no provider segue o padrão {company_id}-{instance_name} para evitar duplicação
$providerInstanceName = $company['id'] . '-' . $input['instance_name'];
$providerClient = ProviderManager::getProvider($provider['id']);
$result = $providerClient->createInstance($providerInstanceName, (string) $instance['id']);
```

### 2. `src/Controllers/InstanceUazapiController.php`

**Linha 199-206**: Ao atualizar o nome de uma instância, também aplica a padronização:

```php
// IMPORTANTE: O nome no provider segue o padrão {company_id}-{instance_name} para evitar duplicação
$providerInstanceName = $instance['company_id'] . '-' . $input['name'];

$providerClient = ProviderManager::getProvider($instance['provider_id']);
$result = $providerClient->updateInstanceName(
    $instance['external_instance_id'],
    $providerInstanceName
);
```

**Linha 216-217**: O nome salvo no banco continua sendo o original (sem o prefixo):

```php
// Atualizar no banco local (salvamos o nome original, sem o prefixo)
Instance::update($instance['id'], ['instance_name' => $input['name']]);
```

### 3. `src/Providers/ProviderInterface.php`

Adicionados novos métodos à interface para suportar operações padrão UAZAPI:
- `connect()`: Conectar instância ao WhatsApp
- `disconnect()`: Desconectar instância do WhatsApp
- `getStatus()`: Obter status e QR code
- `updateInstanceName()`: Atualizar nome da instância
- `getPrivacy()`: Obter configurações de privacidade
- `updatePrivacy()`: Atualizar configurações de privacidade
- `setPresence()`: Definir presença (online/offline)

### 4. `src/Providers/UazapiProvider.php`

Implementados os novos métodos da interface para compatibilidade com o padrão UAZAPI:
- Linhas 295-512: Implementação de todos os métodos adicionados à interface

### 5. `src/Providers/WahaProvider.php`

Já possuía todos os métodos necessários implementados.

## Validação de Nomes Duplicados

Para melhorar a experiência do usuário, foi implementada uma validação que impede a criação ou renomeação de instâncias com nomes duplicados **dentro da mesma empresa**.

### Como Funciona

**Ao criar uma instância (`POST /api/instances`)**:
- Verifica se já existe uma instância com o mesmo nome para aquela empresa
- Se existir, retorna **HTTP 409 Conflict** com a mensagem clara:
  ```json
  {
    "error": "Já existe uma instância com este nome. Por favor, escolha outro nome para sua instância."
  }
  ```

**Ao atualizar o nome (`POST /instance/updateInstanceName`)**:
- Verifica se já existe outra instância com o novo nome para aquela empresa
- Se existir, retorna **HTTP 409 Conflict** com a mensagem:
  ```json
  {
    "error": "Duplicate instance name",
    "message": "Já existe outra instância com este nome. Por favor, escolha outro nome."
  }
  ```

### Implementação

**1. Novo método no `src/Models/Instance.php` (Linha 64-70)**:
```php
public static function findByNameAndCompany(string $instanceName, int $companyId): ?array
{
    return Database::fetchOne(
        'SELECT * FROM instances WHERE instance_name = :instance_name AND company_id = :company_id',
        ['instance_name' => $instanceName, 'company_id' => $companyId]
    );
}
```

**2. Validação em `src/Controllers/InstanceController.php` (Linha 34-49)**:
- Verifica duplicação antes de criar a instância
- Registra log quando uma tentativa é bloqueada

**3. Validação em `src/Controllers/InstanceUazapiController.php` (Linha 198-214)**:
- Verifica duplicação antes de atualizar o nome
- Permite atualizar para o mesmo nome (mesma instância)

## Benefícios

1. ✅ **Isolamento entre empresas**: Cada empresa pode usar o mesmo nome sem conflitos
2. ✅ **Transparência para o usuário**: O usuário vê apenas o nome que ele escolheu
3. ✅ **Compatibilidade**: Funciona tanto com WAHA quanto com UAZAPI
4. ✅ **Escalabilidade**: Suporta qualquer número de empresas com nomes duplicados
5. ✅ **Validação preventiva**: Impede duplicações dentro da mesma empresa com mensagens claras
6. ✅ **Melhor UX**: Erros claros em português antes de tentar criar no provider

## Considerações Técnicas

- O `company_id` é um número inteiro sequencial único para cada empresa
- O separador usado é `-` (hífen)
- A padronização é aplicada automaticamente em todas as operações de criação e atualização
- A remoção do prefixo não é necessária pois ele nunca é armazenado no banco local

## Retrocompatibilidade

⚠️ **Importante**: Instâncias criadas antes desta alteração podem não ter o prefixo `{company_id}-` no provider. 

Para instâncias antigas:
- Elas continuarão funcionando normalmente
- Se o nome for atualizado via `updateInstanceName`, o novo padrão será aplicado
- Recomenda-se migração gradual conforme necessário

## Testando

### Teste 1: Nomes Iguais em Empresas Diferentes (✅ Permitido)

1. **Empresa 1** cria instância com nome "Vendas"
   - No banco: `instance_name = "Vendas"`
   - Na WAHA: `1-Vendas`
   - Resposta: **201 Created**

2. **Empresa 2** cria instância com nome "Vendas"
   - No banco: `instance_name = "Vendas"`
   - Na WAHA: `2-Vendas`
   - Resposta: **201 Created**

✅ Funciona! Empresas diferentes podem usar o mesmo nome.

### Teste 2: Nome Duplicado na Mesma Empresa (❌ Bloqueado)

1. **Empresa 1** cria primeira instância "Vendas"
   - Resposta: **201 Created**

2. **Empresa 1** tenta criar segunda instância "Vendas"
   - Resposta: **409 Conflict**
   ```json
   {
     "error": "Já existe uma instância com este nome. Por favor, escolha outro nome para sua instância."
   }
   ```

❌ Bloqueado! Mesma empresa não pode ter nomes duplicados.

### Teste 3: Atualizar Nome para Nome Existente (❌ Bloqueado)

1. **Empresa 1** tem duas instâncias:
   - Instância A: "Vendas"
   - Instância B: "Suporte"

2. Tentar renomear Instância B para "Vendas":
   ```bash
   POST /instance/updateInstanceName
   Authorization: Bearer {instance_b_token}
   {
     "name": "Vendas"
   }
   ```
   
   - Resposta: **409 Conflict**
   ```json
   {
     "error": "Duplicate instance name",
     "message": "Já existe outra instância com este nome. Por favor, escolha outro nome."
   }
   ```

❌ Bloqueado! Não pode renomear para um nome já existente.

### Teste 4: Atualizar para o Mesmo Nome (✅ Permitido)

1. Renomear Instância A de "Vendas" para "Vendas" (mesmo nome):
   - Resposta: **200 OK**

✅ Permitido! Pode "atualizar" para o mesmo nome (útil para outras atualizações).

