# 🔌 INTEGRATIONS.md - Movimento Livre

Este documento descreve as integrações externas e internas suportadas ou recomendadas pelo plugin **Movimento Livre**, com foco em funcionalidades de geração de documentos, envio de notificações e extensibilidade para outros sistemas sociais.

---

## 🧩 1. Integrações Internas (WordPress/WooCommerce)

### WooCommerce
- Utilização completa da estrutura de Pedidos e Produtos
- Estoque nativo ativado
- Relatórios compatíveis com relatórios WooCommerce

### WordPress
- Utilização de post types e postmeta (`product`, `shop_order`)
- Shortcodes integrados via `add_shortcode()`
- Hooks e filtros WordPress para extensibilidade

---

## 🖨️ 2. Geração de PDFs

### Bibliotecas suportadas:
- [`dompdf/dompdf`](https://github.com/dompdf/dompdf)
- [`tecnickcom/tcpdf`](https://github.com/tecnickcom/TCPDF)

**Requisitos:**
- Extensões PHP habilitadas: `gd`, `mbstring`, `fileinfo`
- Diretório configurado para salvar arquivos: `/wp-content/uploads/movliv/`

**Personalização:**
- Templates localizados em `/templates/pdf/`
- Sobrescritos por temas com estrutura `yourtheme/movimento-livre/pdf/`

---

## 📧 3. Envio de E-mails

### Métodos suportados:
- `wp_mail()` padrão do WordPress
- Plugins SMTP (ex: WP Mail SMTP, Post SMTP)
- Gateways como:
  - SendGrid
  - Amazon SES
  - Mailgun

### Personalização:
- Templates em `/templates/emails/`
- Suporte a filtros:
  - `movliv_email_subject`
  - `movliv_email_body`

---

## 💬 4. Integração com WhatsApp (Opcional)

### Possibilidades:
- Envio de lembretes e confirmações via API de terceiros
- Recomendado: gateway com webhook e autenticação token

### Gateways sugeridos:
- UltraMsg, Gupshup, Z-API, WPPConnect

**Implementação:**
- Hooks disponíveis:
  - `movliv_after_status_change`
  - `movliv_after_formulario_enviado`

---

## ☁️ 5. Google Drive (Futuro)

### Possibilidade de Integração:
- Backup automático de PDFs para conta Drive do Instituto
- Acesso aos documentos para auditoria

### Tecnologias envolvidas:
- Google Drive API
- OAuth 2.0 com tokens de acesso persistente

---

## 🔐 6. Plugins Recomendados

| Finalidade                  | Plugin                              |
|----------------------------|-------------------------------------|
| Gerenciar permissões       | Members ou User Role Editor         |
| SMTP seguro                | WP Mail SMTP                        |
| Formulários extras (frontend) | Fluent Forms ou WPForms           |
| Backup automático          | UpdraftPlus                         |
| Gerador de relatórios CSV  | WP All Export                       |

---

## 🔄 7. Webhooks / Extensões Customizadas

### Endpoints customizados:
- REST API disponível para leitura de status e histórico (futuramente)

### Filtros disponíveis:
- `movliv_filter_produtos_disponiveis`
- `movliv_filter_relatorio_dados`

---

## ✅ Conclusão

O plugin Movimento Livre está preparado para integrar com serviços modernos de comunicação, armazenamento e relatórios, mantendo a compatibilidade com a estrutura nativa do WordPress e WooCommerce. Novas integrações podem ser adicionadas sob demanda ou via extensões dedicadas.

