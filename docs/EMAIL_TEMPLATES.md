# 📧 EMAIL\_TEMPLATES.md - Movimento Livre

Este documento descreve os modelos de e-mails enviados automaticamente pelo plugin **Movimento Livre**, utilizados para comunicação com solicitantes, colaboradores e administradores do sistema.

Os e-mails podem ser personalizados via filtros e templates, e integrados com gateways SMTP ou APIs externas como SendGrid, WhatsApp Gateway, entre outros.

---

## 📤 1. Confirmação de Empréstimo

### Disparo:

Após o envio do Formulário de Empréstimo

### Destinatário:

Solicitante (baseado no CPF/pedido)

### Assunto:

"✅ Empréstimo Confirmado - Instituto Bernardo Ferreira"

### Corpo:

```
Olá [NOME],

Seu pedido de empréstimo foi confirmado com sucesso. A cadeira com a TAG [TAG/SKU] foi registrada como emprestada em seu nome.

📅 Data de retirada: [DATA_RETIRADA]
📦 Código do empréstimo: #[PEDIDO_ID]

Por favor, lembre-se de realizar a devolução até:
📆 [DATA_PREVISTA_DEVOLUCAO]

Em caso de dúvidas, entre em contato conosco.

Obrigado por fazer parte do Movimento Livre 💙
```

---

## 🔁 2. Lembrete de Devolução

### Disparo:

3 dias antes da data prevista de devolução (via cron agendado)

### Destinatário:

Solicitante

### Assunto:

"📣 Lembrete: devolução da cadeira até [DATA\_PREVISTA]"

### Corpo:

```
Olá [NOME],

Este é um lembrete amigável de que a cadeira de rodas com a TAG [TAG] emprestada para você está prevista para devolução até [DATA_PREVISTA].

Pedimos que acesse o site e preencha o formulário de devolução para concluir o processo.

🔗 Acesse aqui: [LINK_FORMULARIO_DEVOLUCAO]

Caso precise de mais tempo, entre em contato com nossa equipe.

Instituto Bernardo Ferreira
```

---

## 📥 3. Confirmação de Devolução

### Disparo:

Após envio do Formulário de Devolução

### Destinatário:

Solicitante

### Assunto:

"📥 Devolução registrada com sucesso"

### Corpo:

```
Olá [NOME],

Confirmamos que sua devolução da cadeira de rodas TAG [TAG] foi registrada com sucesso.

A cadeira agora está em avaliação técnica.
Você receberá novo contato caso seja necessário prestar esclarecimentos.

Obrigado pela responsabilidade e cuidado!

Equipe do Movimento Livre
```

---

## 🧪 4. Alerta Interno - Avaliação Necessária

### Disparo:

Após Formulário de Devolução

### Destinatário:

Colaboradores com permissão "movliv\_avaliador"

### Assunto:

"⚠️ Avaliação técnica pendente - Cadeira [TAG]"

### Corpo:

```
Olá equipe,

A cadeira [TAG/SKU] foi devolvida e precisa passar por avaliação técnica.

📄 Gerar e preencher o formulário de avaliação interna o quanto antes.
🔗 Link direto: [LINK_FORM_AVALIACAO_PRODUTO]

Atenciosamente,
Sistema Movimento Livre
```

---

## 🔧 5. Alerta de Reavaliação (Manutenção)

### Disparo:

Quando uma cadeira é marcada como "Em Manutenção"

### Destinatário:

Colaboradores técnicos

### Assunto:

"🔧 Cadeira em manutenção - reavaliação necessária"

### Corpo:

```
Olá,

A cadeira [TAG] passou por uma avaliação e foi marcada como "Em Manutenção".

Após o conserto, será necessário preencher uma nova ficha técnica de avaliação.

🛠️ Link para reavaliar: [LINK_FORM_AVALIACAO]

Obrigado pelo cuidado e compromisso!
```

---

## 🔐 Observações Técnicas

- Os e-mails são enviados usando `wp_mail()` com suporte a SMTP externo
- Hooks disponíveis:
  - `movliv_email_before_send`
  - `movliv_email_subject`
  - `movliv_email_body`
- Templates podem ser sobrescritos por temas usando `movimento-livre/emails/`

---

Este documento garante consistência na comunicação com usuários e equipe técnica e pode ser expandido conforme novas etapas forem adicionadas ao fluxo.

