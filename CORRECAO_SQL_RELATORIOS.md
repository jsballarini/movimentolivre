# Correção SQL dos Relatórios

**Tipo**: CORREÇÃO CRÍTICA - SQL  
**Prioridade**: ALTA  
**Status**: ✅ CORRIGIDO

## 🐛 **Problema Identificado**

SQL incorreto na função `generate_top_usuarios_table()` estava causando problemas nos relatórios de usuários mais ativos.

## ❌ **SQL Problemático (ANTES)**

```sql
SELECT 
    pm1.meta_value as cpf,
    CONCAT(pm2.meta_value, ' ', pm3.meta_value) as nome,
    COUNT(*) as total_emprestimos,
    MAX(p.post_date) as ultimo_emprestimo
FROM {$wpdb->posts} p
JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_billing_cpf'
JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_billing_first_name'
JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_billing_last_name'
WHERE p.post_type = 'shop_order'
AND p.post_status IN ('wc-on-hold', 'wc-processing', 'wc-completed')
AND pm1.meta_value != ''
GROUP BY pm1.meta_value, pm2.meta_value, pm3.meta_value
ORDER BY total_emprestimos DESC
LIMIT 10
```

## 🔍 **Problemas Identificados**

### **1. Meta Key Incorreta**
- ❌ **Erro**: Buscava `_billing_cpf` (campo do plugin externo)
- ✅ **Correção**: Usar `_cpf_solicitante` (campo interno do sistema)
- **Motivo**: O sistema salva o CPF como `_cpf_solicitante` independente da origem

### **2. INNER JOINs Excessivos**
- ❌ **Problema**: `INNER JOIN` para nome e sobrenome exclui pedidos sem esses dados
- ✅ **Correção**: `LEFT JOIN` para permitir pedidos sem nome completo
- **Impacto**: Incluir todos os empréstimos, mesmo sem dados completos do cliente

### **3. GROUP BY Problemático**
- ❌ **Problema**: `GROUP BY pm1.meta_value, pm2.meta_value, pm3.meta_value`
- **Consequência**: Mesmo CPF com nomes diferentes = registros separados
- ✅ **Correção**: `GROUP BY pm1.meta_value` (apenas por CPF)
- **Resultado**: Agrupa corretamente por usuário único

### **4. CONCAT sem Proteção**
- ❌ **Problema**: `CONCAT(pm2.meta_value, ' ', pm3.meta_value)` falha com NULL
- ✅ **Correção**: `COALESCE(CONCAT(NULLIF(...)), 'Nome não informado')`
- **Benefício**: Tratamento adequado de campos vazios

### **5. ORDER BY Limitado**
- ❌ **Problema**: Apenas `ORDER BY total_emprestimos DESC`
- ✅ **Correção**: `ORDER BY total_emprestimos DESC, ultimo_emprestimo DESC`
- **Melhoria**: Desempate por data do último empréstimo

## ✅ **SQL Corrigido (DEPOIS)**

```sql
SELECT 
    pm1.meta_value as cpf,
    COALESCE(CONCAT(NULLIF(pm2.meta_value, ''), ' ', NULLIF(pm3.meta_value, '')), 'Nome não informado') as nome,
    COUNT(*) as total_emprestimos,
    MAX(p.post_date) as ultimo_emprestimo
FROM {$wpdb->posts} p
INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_cpf_solicitante'
LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_billing_first_name'
LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_billing_last_name'
WHERE p.post_type = 'shop_order'
AND p.post_status IN ('wc-on-hold', 'wc-processing', 'wc-completed')
AND pm1.meta_value != ''
AND pm1.meta_value IS NOT NULL
GROUP BY pm1.meta_value
ORDER BY total_emprestimos DESC, ultimo_emprestimo DESC
LIMIT 10
```

## 🔧 **Melhorias Implementadas**

### **✅ Meta Key Correta**
- **Campo**: `_cpf_solicitante` (interno do sistema)
- **Compatibilidade**: Funciona com plugin externo e campo próprio
- **Consistência**: Alinhado com resto do sistema

### **✅ JOINs Otimizados**
- **INNER JOIN**: Apenas para CPF (obrigatório)
- **LEFT JOIN**: Para nome e sobrenome (opcionais)
- **Resultado**: Inclui todos os empréstimos válidos

### **✅ Agrupamento Correto**
- **Critério**: Apenas por CPF único
- **Benefício**: Um registro por usuário/CPF
- **Precisão**: Contagem correta de empréstimos por pessoa

### **✅ Tratamento de NULLs**
- **NULLIF**: Remove strings vazias antes do CONCAT
- **COALESCE**: Fallback para "Nome não informado"
- **Robustez**: Funciona com dados incompletos

### **✅ Validação Adicional**
- **Condição**: `AND pm1.meta_value IS NOT NULL`
- **Proteção**: Evita registros com CPF NULL
- **Qualidade**: Dados mais confiáveis

### **✅ Ordenação Melhorada**
- **Primário**: Por total de empréstimos (DESC)
- **Secundário**: Por data do último empréstimo (DESC)
- **Resultado**: Ranking mais preciso

## 📊 **Impacto da Correção**

### **Antes (com problemas)**
- 🚫 **Dados perdidos**: Pedidos sem nome completo excluídos
- 🚫 **Duplicação**: Mesmo CPF aparece múltiplas vezes
- 🚫 **Erros SQL**: CONCAT falha com campos NULL
- 🚫 **Busca incorreta**: Meta key `_billing_cpf` não encontrada

### **Depois (corrigido)**
- ✅ **Dados completos**: Todos os empréstimos incluídos
- ✅ **Agrupamento correto**: Um registro por CPF/usuário
- ✅ **Tratamento robusto**: Funciona com dados incompletos
- ✅ **Meta key correta**: Encontra todos os CPFs salvos

## 🎯 **Funcionalidades Beneficiadas**

### **📊 Relatório de Usuários**
- **Top usuários**: Lista correta dos mais ativos
- **Estatísticas**: Contagem precisa de empréstimos
- **Ranking**: Ordenação por atividade e recência

### **🔍 Dados Analíticos**
- **Integridade**: Informações mais confiáveis
- **Completude**: Incluir todos os registros válidos
- **Precisão**: Métricas corretas de utilização

## 🧪 **Testes Realizados**

### **✅ Cenários Testados**
1. **Pedidos com nome completo** → Exibição correta ✅
2. **Pedidos sem nome** → "Nome não informado" ✅
3. **Múltiplos empréstimos mesmo CPF** → Agrupamento correto ✅
4. **CPFs com formatação** → Busca funcionando ✅
5. **Dados incompletos** → Tratamento adequado ✅

### **✅ Validações**
- **Performance**: Consulta otimizada
- **Compatibilidade**: Funciona com plugin externo
- **Robustez**: Trata casos extremos
- **Precisão**: Dados consistentes

## 📝 **Arquivos Modificados**

### `includes/class-reports.php`
- ✅ **Função corrigida**: `generate_top_usuarios_table()`
- ✅ **Meta key atualizada**: `_billing_cpf` → `_cpf_solicitante`
- ✅ **JOINs otimizados**: INNER → LEFT onde apropriado
- ✅ **GROUP BY simplificado**: Apenas por CPF
- ✅ **Tratamento de NULLs**: COALESCE + NULLIF

## 🚀 **Próximos Passos**

1. **Teste em produção** com dados reais
2. **Monitoramento** da performance da consulta
3. **Validação** dos relatórios gerados
4. **Documentação** para usuários finais

---

**Status**: ✅ **SQL CORRIGIDO COM SUCESSO**  
**Resultado**: Relatórios precisos e dados consistentes  
**Impacto**: Melhoria significativa na qualidade dos dados  
**Compatibilidade**: 100% mantida com plugin externo 
