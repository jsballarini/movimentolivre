# Correção dos Status de Pedidos nos Relatórios

## Problema Identificado

Os relatórios estavam exibindo a mensagem "Nenhum usuário encontrado com empréstimos registrados" mesmo quando existiam usuários com empréstimos ativos. O problema era causado pelo uso incorreto dos status de pedidos nas consultas SQL.

## Causa Raiz

As consultas SQL estavam usando status de pedidos com o prefixo `'wc-'`:
- `'wc-on-hold'`
- `'wc-processing'`
- `'wc-completed'`
- `'wc-cancelled'`

Porém, no banco de dados do WooCommerce, os status corretos são **sem** o prefixo `'wc-'`:
- `'on-hold'`
- `'processing'`
- `'completed'`
- `'cancelled'`

## Soluções Implementadas

### 1. Correção no método `generate_top_usuarios_table()`
**Arquivo:** `includes/class-reports.php` (linha 941)
- **Antes:** `AND p.post_status IN ('wc-on-hold', 'wc-processing', 'wc-completed')`
- **Depois:** `AND p.post_status IN ('on-hold', 'processing', 'completed')`

### 2. Correção no método `get_general_stats()`
**Arquivo:** `includes/class-reports.php` (múltiplas linhas)

#### Total de empréstimos:
- **Antes:** `AND p.post_status IN ('wc-on-hold', 'wc-processing', 'wc-completed')`
- **Depois:** `AND p.post_status IN ('on-hold', 'processing', 'completed')`

#### Empréstimos este mês:
- **Antes:** `AND p.post_status IN ('wc-on-hold', 'wc-processing', 'wc-completed')`
- **Depois:** `AND p.post_status IN ('on-hold', 'processing', 'completed')`

#### Usuários únicos:
- **Antes:** `AND p.post_status IN ('wc-on-hold', 'wc-processing', 'wc-completed')`
- **Depois:** `AND p.post_status IN ('on-hold', 'processing', 'completed')`

#### Novos usuários este mês:
- **Antes:** `AND p.post_status IN ('wc-on-hold', 'wc-processing', 'wc-completed')` (2 instâncias)
- **Depois:** `AND p.post_status IN ('on-hold', 'processing', 'completed')` (2 instâncias)

#### Taxa de devolução:
- **Antes:** `AND p.post_status = 'wc-processing'` e `AND p.post_status = 'wc-completed'`
- **Depois:** `AND p.post_status = 'processing'` e `AND p.post_status = 'completed'`

### 3. Correção no método `get_performance_stats()`
**Arquivo:** `includes/class-reports.php` (linha 706)
- **Antes:** `AND p1.post_status = 'wc-processing'`
- **Depois:** `AND p1.post_status = 'processing'`

### 4. Correção no método `generate_cadeiras_performance_table()`
**Arquivo:** `includes/class-reports.php` (linha 872)
- **Antes:** `AND o.post_status IN ('wc-processing', 'wc-completed')`
- **Depois:** `AND o.post_status IN ('processing', 'completed')`

### 5. Correção no método `get_emprestimos_mensal_data()`
**Arquivo:** `includes/class-reports.php` (linha 1216)
- **Antes:** `AND post_status IN ('wc-processing', 'wc-completed')`
- **Depois:** `AND post_status IN ('processing', 'completed')`

## Resultado

- ✅ Os relatórios agora identificam corretamente os usuários com empréstimos
- ✅ A mensagem de erro não aparece mais quando existem dados válidos
- ✅ Todas as estatísticas e contadores estão funcionando corretamente
- ✅ Os gráficos e tabelas exibem dados precisos

## Testes Realizados

- Verificação de que não há mais status com prefixo `'wc-'` no arquivo `class-reports.php`
- Confirmação de que os relatórios mostram dados quando existem empréstimos
- Validação da consistência entre diferentes abas dos relatórios

## Data da Correção
**Data:** 02/01/2025
**Desenvolvedor:** Juliano Ballarini
**Versão:** 0.13.1

## Observações Técnicas

Esta correção resolve uma inconsistência crítica que estava causando falsos negativos nos relatórios. O problema era específico de consultas SQL diretas que não utilizavam as funções nativas do WooCommerce, que já tratam corretamente os prefixos dos status. 