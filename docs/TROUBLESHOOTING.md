# 🛠️ TROUBLESHOOTING.md - Movimento Livre

Este documento oferece soluções para os problemas mais comuns que podem ocorrer durante o uso e a configuração do plugin **Movimento Livre** em um ambiente WordPress/WooCommerce.

---

## ❌ Erros Comuns e Soluções

### 1. PDFs não estão sendo gerados

**Causa provável:** Falta da biblioteca `dompdf` ou `tcpdf`, ou permissões de escrita no diretório.

**Solução:**

- Verifique se as extensões PHP `gd`, `mbstring`, `fileinfo` estão ativas
- Confirme que a pasta `/wp-content/uploads/movliv/` existe e tem permissões 755 ou 775
- Confira se há conflitos com outros plugins de PDF

---

### 2. Formulários não aparecem após pedido

**Causa provável:** Shortcodes não foram inseridos corretamente na página, ou pedido não está no status esperado.

**Solução:**

- Verifique se o shortcode `[movliv_form_emprestimo]` está na página atribuída a "Aguardando Empréstimo"
- O pedido precisa estar com status `Aguardando` e a cadeira com status `Pronta`

---

### 3. Pedido não muda de status após envio de formulário

**Causa provável:** Hook de atualização de status não foi disparado ou PDF não foi salvo corretamente.

**Solução:**

- Verifique se o PDF foi salvo em `/uploads/movliv/`
- Cheque o log de erros do servidor (`debug.log`)
- Reative o plugin para forçar reconfiguração de hooks

---

### 4. Não consigo adicionar a cadeira ao carrinho

**Causa provável:** Cadeira não está com status `Pronta` ou estoque = 0

**Solução:**

- Vá até o produto (cadeira) e verifique:
  - Estoque: 1 unidade
  - Status da cadeira = `Pronta`
- Atualize o produto e teste novamente

---

### 5. CPF com 2 empréstimos bloqueia novo pedido mesmo após devolução

**Causa provável:** Status antigo ainda permanece como `Emprestado`

**Solução:**

- Verifique se o pedido realmente foi alterado para status `Devolvido`
- Cheque se os formulários foram enviados corretamente
- Use o relatório por CPF para identificar status incorretos

---

### 6. E-mails não estão sendo enviados

**Causa provável:** WordPress não configurado para SMTP ou bloqueio por firewall

**Solução:**

- Instale plugin SMTP como WP Mail SMTP
- Teste envio de e-mail pelo painel
- Verifique as configurações da hospedagem (porta 587, TLS ativado)

---

## 🔍 Logs e Debug

### Como ativar o debug do WordPress

Edite o arquivo `wp-config.php` e adicione:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Os logs serão salvos em `/wp-content/debug.log`

---

## 🧰 Suporte e Contribuição

- Sempre use a versão mais recente do plugin
- Em caso de erro persistente, envie os logs e capturas de tela
- Contribuições podem ser feitas via GitHub ou canal do Instituto

---

Este guia de resolução de problemas deve ser incluído na documentação final entregue à equipe técnica e de atendimento do Instituto Bernardo Ferreira.

