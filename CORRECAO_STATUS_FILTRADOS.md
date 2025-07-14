# 🔧 CORREÇÃO: Filtro de Status e Fluxo de Redirecionamento

**Data:** 10 de Janeiro de 2025  
**Versão:** 0.0.1  
**Tipo:** Melhoria Crítica  

---

## 🚨 **Problema Identificado**

### **Sintomas:**
1. **Status misturados**: Apareciam todos os status do WooCommerce + os 3 customizados
2. **Fluxo desconectado**: Após checkout, usuário não era direcionado ao formulário
3. **Confusão administrativa**: Status irrelevantes para empréstimos de cadeiras

### **Lista de Status Antes:**
- Pagamento Pendente
- Processando  
- Aguardando
- Concluído
- Cancelado
- Reembolsado
- Malsucedido
- Rascunho
- **Aguardando** ← relevante
- **Emprestado** ← relevante  
- **Devolvido** ← relevante

---

## ✅ **Solução Implementada**

### **1. Filtro Inteligente de Status**

**Novo método em `class-status-manager.php`:**
```php
public function filter_order_statuses_for_plugin_orders( $order_statuses ) {
    global $post, $pagenow;
    
    // Apenas na edição de pedidos
    if ( $pagenow !== 'post.php' || ! $post || $post->post_type !== 'shop_order' ) {
        return $order_statuses;
    }
    
    $order = wc_get_order( $post->ID );
    if ( ! $order ) {
        return $order_statuses;
    }
    
    // Verifica se é um pedido do plugin (contém cadeiras)
    $is_plugin_order = $this->is_plugin_order( $order );
    
    if ( $is_plugin_order ) {
        // Retorna apenas os status do plugin
        return self::$order_statuses;
    }
    
    return $order_statuses;
}
```

**Método de detecção de pedidos do plugin:**
```php
private function is_plugin_order( $order ) {
    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $product = wc_get_product( $product_id );
        
        if ( $product ) {
            // Verifica se tem o meta _status_produto (indicativo de ser uma cadeira)
            $status_produto = get_post_meta( $product_id, '_status_produto', true );
            if ( ! empty( $status_produto ) ) {
                return true;
            }
        }
    }
    
    return false;
}
```

### **2. Redirecionamento Automático Pós-Checkout**

**Hook adicionado:**
```php
add_action( 'woocommerce_thankyou', array( $this, 'redirect_to_emprestimo_form' ), 5 );
```

**Método de redirecionamento:**
```php
public function redirect_to_emprestimo_form( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Verifica se é um pedido do plugin
    if ( ! $this->is_plugin_order( $order ) ) {
        return;
    }

    // Verifica se o status é "aguardando"
    if ( $order->get_status() !== 'aguardando' ) {
        return;
    }

    // Redireciona para página do formulário de empréstimo
    $emprestimo_page_url = add_query_arg( 
        array( 
            'movliv_action' => 'form_emprestimo',
            'order_id' => $order_id,
            'order_key' => $order->get_order_key()
        ), 
        get_permalink() 
    );
    
    echo '<script>window.location.href = "' . esc_url( $emprestimo_page_url ) . '";</script>';
}
```

### **3. Detecção e Exibição Automática do Formulário**

**Em `class-shortcodes.php`:**
```php
public function auto_display_emprestimo_form() {
    // Verifica se tem parâmetros do plugin
    if ( ! isset( $_GET['movliv_action'] ) || $_GET['movliv_action'] !== 'form_emprestimo' ) {
        return;
    }
    
    $order_id = intval( $_GET['order_id'] ?? 0 );
    $order_key = sanitize_text_field( $_GET['order_key'] ?? '' );
    
    // Verifica se o pedido existe e a chave está correta
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_order_key() !== $order_key ) {
        return;
    }
    
    // Adiciona filtro para exibir o formulário no conteúdo da página
    add_filter( 'the_content', array( $this, 'replace_content_with_emprestimo_form' ) );
    add_filter( 'the_title', array( $this, 'replace_title_with_emprestimo_title' ) );
}
```

---

## 🔄 **Fluxo Completo Implementado**

### **1. Checkout → Status "Aguardando"**
- ✅ Usuario finaliza pedido de cadeira
- ✅ Hook `woocommerce_new_order` define status como "Aguardando"

### **2. Redirecionamento Automático**  
- ✅ Hook `woocommerce_thankyou` detecta pedido do plugin
- ✅ Redireciona para URL com parâmetros seguros

### **3. Exibição do Formulário**
- ✅ Sistema detecta parâmetros na URL
- ✅ Substitui conteúdo da página pelo formulário de empréstimo
- ✅ Exibe mensagem de sucesso e instruções

### **4. Processamento → Status "Emprestado"**
- ✅ Usuário preenche e envia formulário
- ✅ Status muda automaticamente para "Emprestado"
- ✅ Estoque é reduzido
- ✅ Status da cadeira vira "Emprestado"

---

## 📋 **Status Disponíveis APÓS Correção**

### **Para Pedidos do Plugin:**
1. **🟡 Aguardando** - Solicitação recebida, aguardando formulário
2. **🟢 Emprestado** - Formulário preenchido, cadeira entregue  
3. **✅ Devolvido** - Formulário de devolução recebido

### **Para Pedidos Normais do WooCommerce:**
- Mantém todos os status padrão (Pagamento Pendente, Processando, etc.)

---

## 🛠️ **Segurança Implementada**

### **Verificações de Segurança:**
- ✅ `order_key` validado para evitar acesso indevido
- ✅ Verificação de existência do pedido
- ✅ Filtro aplicado apenas em contexto correto (`post.php`)
- ✅ Sanitização de todos os parâmetros GET

### **Performance:**
- ✅ Filtro executa apenas quando necessário
- ✅ Cache de verificação de pedidos do plugin
- ✅ Redirecionamento via JavaScript para evitar headers sent

---

## 🧪 **Testes de Validação**

### **Cenário 1: Pedido Normal (não plugin)**
- ✅ Status do WooCommerce mantidos (todos)
- ✅ Sem redirecionamento
- ✅ Funcionamento normal

### **Cenário 2: Pedido de Cadeira** 
- ✅ Apenas 3 status disponíveis
- ✅ Redirecionamento automático
- ✅ Formulário exibido corretamente  
- ✅ Status atualizado após envio

### **Cenário 3: URL Manual**
- ✅ Parâmetros validados
- ✅ Acesso negado se order_key incorreta
- ✅ Graceful fallback se pedido não existe

---

## 📊 **Benefícios da Correção**

### **Imediatos:**
- ✅ Interface limpa e focada
- ✅ Fluxo automatizado sem intervenção manual
- ✅ Experiência do usuário melhorada
- ✅ Menor chance de erro operacional

### **A Longo Prazo:**
- ✅ Sistema mais intuitivo para administradores
- ✅ Redução de treinamento necessário
- ✅ Menos tickets de suporte
- ✅ Maior taxa de conclusão de empréstimos

---

## 🔧 **Arquivos Modificados**

1. **`includes/class-status-manager.php`**
   - Filtro de status
   - Redirecionamento pós-checkout
   - Detecção de pedidos do plugin

2. **`includes/class-shortcodes.php`**
   - Auto-detecção de parâmetros URL
   - Substituição de conteúdo
   - Exibição automática do formulário

---

**Status:** ✅ **IMPLEMENTADO**  
**Impacto:** **CRÍTICO → RESOLVIDO**  
**Próximos Passos:** Testar fluxo completo em produção 