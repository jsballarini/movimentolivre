# Correção da URL do Formulário de Empréstimo

**Data:** 13/07/2025  
**Versão:** 0.0.2  
**Autor:** Juliano Ballarini  

## Problema Identificado

A URL do formulário de empréstimo estava sendo gerada com caracteres codificados incorretamente após o checkout:

```
http://localhost/movimentolivre/formulario-de-emprestimo/?movliv_action=form_emprestimo#038;order_id=180&#038;order_key=wc_order_f4lG6xiyKJsGS
```

### Comportamento Incorreto
- Caracteres `#038;` aparecendo na URL
- Parâmetros não sendo reconhecidos corretamente
- Formulário não carregando os dados do pedido

## Causa Raiz

A codificação dos parâmetros da URL estava sendo feita no PHP e depois passada para o JavaScript, causando dupla codificação dos caracteres especiais.

## Solução Implementada

1. **Uso da URL API do JavaScript**:
   - `new URL()` para criar objeto URL
   - `searchParams.append()` para adicionar parâmetros
   - `toString()` para gerar a URL final

2. **Código Corrigido**:
```javascript
// Constrói URL com parâmetros
var url = new URL('<?php echo esc_js( $base_url ); ?>');
url.searchParams.append('movliv_action', 'form_emprestimo');
url.searchParams.append('order_id', '<?php echo esc_js( $order_id ); ?>');
url.searchParams.append('order_key', '<?php echo esc_js( $order->get_order_key() ); ?>');

// Redireciona
window.location.href = url.toString();
```

3. **URL Correta**:
```
http://localhost/movimentolivre/formulario-de-emprestimo/?movliv_action=form_emprestimo&order_id=180&order_key=wc_order_f4lG6xiyKJsGS
```

## Impacto da Correção

1. **Funcional**:
   - URL limpa e corretamente formatada
   - Parâmetros sendo passados corretamente
   - Formulário carregando dados do pedido

2. **Experiência do Usuário**:
   - Redirecionamento automático funcionando
   - Sem erros de carregamento
   - Fluxo contínuo do checkout ao formulário

## Arquivos Modificados

- `includes/class-order-hooks.php`
  - Método `add_redirect_script()`
  - Implementação usando URL API do JavaScript

## Testes Realizados

1. ✅ Checkout de pedido gratuito
2. ✅ Redirecionamento automático
3. ✅ Carregamento do formulário
4. ✅ Validação dos parâmetros
5. ✅ Exibição dos dados do pedido

## Notas Adicionais

Esta correção utiliza a moderna URL API do JavaScript para garantir a correta codificação dos parâmetros da URL, evitando problemas de codificação dupla que ocorriam na solução anterior. 
