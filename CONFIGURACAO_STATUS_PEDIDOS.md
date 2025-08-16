# 📋 CONFIGURAÇÃO DOS STATUS DE PEDIDOS - Movimento Livre

**Data:** 10 de Julho de 2025  
**Versão:** 0.0.1  
**Tipo:** Especificação de Status - Implementação Final  

---

## 🎯 **OBJETIVO DA CONFIGURAÇÃO**

Este documento define a configuração final dos status de pedidos do WooCommerce no plugin Movimento Livre, removendo status desnecessários e mantendo apenas os relevantes para o sistema de empréstimos de cadeiras de rodas.

---

## 🗑️ **STATUS REMOVIDOS DO WOOCOMMERCE**

Os seguintes status nativos do WooCommerce foram **REMOVIDOS** por não serem necessários para empréstimos:

### ❌ **Status Excluídos:**
- **Pagamento Pendente** (`wc-pending`) → Não se aplica a empréstimos gratuitos
- **Reembolsado** (`wc-refunded`) → Não há reembolso em empréstimos
- **Malsucedido** (`wc-failed`) → Não relevante para empréstimos
- **Rascunho** (`wc-checkout-draft`) → Não usado no fluxo de empréstimos

**Motivo da Remoção:** Estes status causam confusão na interface administrativa e não têm aplicação prática no contexto de empréstimos solidários de cadeiras de rodas.

---

## ✅ **STATUS MANTIDOS E CONFIGURADOS**

### **1. 🟡 Aguardando** 
- **Status WooCommerce:** `wc-on-hold` (NATIVO)
- **Nome Original:** "Aguardando" 
- **Nome no Plugin:** "Aguardando" (sem alteração)
- **Quando Aplicado:** Automaticamente após criação do pedido de cadeira
- **Descrição:** Solicitação recebida, aguardando preenchimento do formulário de retirada

### **2. ✅ Cancelado**
- **Status WooCommerce:** `wc-cancelled` (NATIVO)
- **Nome Original:** "Cancelado"
- **Nome no Plugin:** "Cancelado" (sem alteração)
- **Quando Aplicado:** Manualmente pelo administrador se solicitação for cancelada
- **Descrição:** Empréstimo cancelado por solicitação do usuário ou impossibilidade de atendimento

### **3. 🟢 Emprestado**
- **Status WooCommerce:** `wc-processing` (NATIVO)
- **Nome Original:** "Processando"
- **Nome no Plugin:** "Emprestado" (RENOMEADO)
- **Quando Aplicado:** Automaticamente após envio do formulário de empréstimo
- **Descrição:** Cadeira foi entregue ao usuário, empréstimo ativo

### **4. 🔵 Devolvido**
- **Status WooCommerce:** `wc-completed` (NATIVO)
- **Nome Original:** "Concluído"
- **Nome no Plugin:** "Devolvido" (RENOMEADO)
- **Quando Aplicado:** Automaticamente após envio do formulário de devolução
- **Descrição:** Cadeira foi devolvida, empréstimo finalizado

---

## 🔄 **FLUXO COMPLETO DOS STATUS**

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   SOLICITAÇÃO   │───▶│   AGUARDANDO    │───▶│   EMPRESTADO    │───▶│   DEVOLVIDO     │
│   (Checkout)    │    │  (wc-on-hold)   │    │ (wc-processing) │    │ (wc-completed)  │
└─────────────────┘    └─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                                               
                                ▼                                               
                       ┌─────────────────┐                                      
                       │   CANCELADO     │                                      
                       │ (wc-cancelled)  │                                      
                       └─────────────────┘                                      
```

---

## 🛠️ **IMPLEMENTAÇÃO TÉCNICA**

### **1. Filtro de Status (PHP)**
```php
// Remove status indesejados apenas para pedidos do plugin
public function filter_unwanted_statuses( $order_statuses ) {
    // Remove: Pagamento Pendente, Reembolsado, Malsucedido, Rascunho
    $unwanted_statuses = array(
        'wc-pending',    
        'wc-refunded',   
        'wc-failed',     
        'wc-checkout-draft'
    );
    
    foreach ( $unwanted_statuses as $status ) {
        unset( $order_statuses[ $status ] );
    }
    
    return $order_statuses;
}
```

### **2. Renomeação de Labels (PHP)**
```php
// Renomeia apenas para pedidos do plugin
public function rename_order_statuses( $order_statuses ) {
    $order_statuses['wc-processing'] = 'Emprestado';  // era "Processando"
    $order_statuses['wc-completed'] = 'Devolvido';    // era "Concluído"
    // 'wc-on-hold' = "Aguardando" (já é o padrão)
    // 'wc-cancelled' = "Cancelado" (já é o padrão)
    
    return $order_statuses;
}
```

### **3. Filtro JavaScript (Frontend)**
```javascript
// Filtra apenas os 4 status permitidos
var allowedStatuses = [
    'wc-on-hold',      // Aguardando
    'wc-processing',   // Emprestado 
    'wc-completed',    // Devolvido
    'wc-cancelled'     // Cancelado
];
```

---

## 🎯 **VANTAGENS DESTA CONFIGURAÇÃO**

### **✅ Interface Limpa**
- Apenas **4 status relevantes** aparecem nos pedidos de cadeiras
- Remove confusão com status desnecessários
- Interface administrativa mais clara

### **✅ Compatibilidade Total**
- Usa **status nativos** do WooCommerce (não cria status customizados)
- **Zero conflitos** com plugins e temas
- **Atualizações seguras** do WooCommerce

### **✅ Fluxo Otimizado**
- **Transições automáticas** baseadas em formulários
- **Lógica clara** para cada etapa do empréstimo
- **Rastreamento completo** do ciclo de vida

### **✅ Manutenção Simplificada**
- **Menos código complexo** (não registra status customizados)
- **Debug facilitado** (usa estrutura nativa)
- **Performance otimizada** (aproveita cache do WooCommerce)

---

## 📊 **VALIDAÇÃO DA IMPLEMENTAÇÃO**

### **Teste de Status (Administrador)**
1. Criar pedido com cadeira → Status deve ser "Aguardando"
2. Editar pedido → Deve mostrar apenas 4 opções no dropdown
3. Alterar para "Emprestado" → Deve funcionar normalmente
4. Alterar para "Devolvido" → Deve funcionar normalmente

### **Teste de Compatibilidade**
1. Verificar relatórios do WooCommerce → Status devem aparecer
2. Testar outros plugins → Não deve haver conflitos
3. Exportar/importar pedidos → Deve funcionar
4. Buscar pedidos por status → Deve encontrar normalmente

---

## 🔧 **SOLUÇÃO DE PROBLEMAS**

### **Status Não Filtrados**
- Verificar se o pedido contém produtos de cadeira
- Confirmar que JavaScript está carregando
- Verificar logs do WordPress para erros

### **Labels Não Renomeados**
- Confirmar que filtros PHP estão ativos
- Verificar ordem de prioridade dos filtros
- Limpar cache se necessário

### **Transições Não Funcionam**
- Verificar hooks de mudança de status
- Confirmar que formulários estão enviando dados
- Verificar logs para debugging

---

**📧 Suporte:** Para dúvidas sobre esta configuração, consulte a documentação técnica completa em `/docs/` ou entre em contato com a equipe de desenvolvimento. 
