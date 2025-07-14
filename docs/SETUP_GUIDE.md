# 🚀 SETUP_GUIDE.md - Movimento Livre

Este guia passo a passo orienta a instalação, configuração inicial e operação básica do plugin **Movimento Livre** em um site WordPress com WooCommerce.

---

## 📦 Requisitos Técnicos

- WordPress 6.0 ou superior
- WooCommerce 7.0 ou superior
- PHP 8.0 ou superior
- Extensão PHP `dompdf` ou `tcpdf` habilitada para geração de PDFs

---

## 🛠️ 1. Instalação

### A. Manualmente via FTP ou Gerenciador de Arquivos
1. Faça upload da pasta do plugin para `wp-content/plugins/movimento-livre`
2. Acesse o painel do WordPress > Plugins
3. Ative o plugin **Movimento Livre**

### B. Via painel do WordPress (ZIP)
1. Vá em *Plugins > Adicionar novo > Enviar plugin*
2. Faça upload do arquivo ZIP do plugin
3. Clique em *Instalar agora* e depois em *Ativar*

---

## ⚙️ 2. Configuração Inicial

Após a ativação:

1. O plugin criará os papéis:
   - `movliv_colaborador`
   - `movliv_avaliador`
   - `movliv_admin`

2. O menu **Movimento Livre** será adicionado ao painel
3. Acesse *Movimento Livre > Configurações* e defina:
   - Diretório dos formulários gerados (default: `/wp-content/uploads/movliv/`)
   - Responsáveis técnicos (usuários com permissão de avaliação)
   - Dias para envio de lembrete antes da devolução (ex: 3 dias)

---

## 🪑 3. Cadastro de Cadeiras

1. Vá em *Produtos > Adicionar novo*
2. Preencha:
   - Nome da cadeira
   - SKU com a TAG física (identificador da cadeira)
   - Estoque = 1 unidade
   - Tipo: Produto simples
   - Preço: R$0,00
3. No metabox "Status da Cadeira": selecione **Pronta**

**Importante:**
- Cadeiras com status ≠ Pronta não poderão ser adicionadas ao carrinho

---

## 📋 4. Simulando um Empréstimo

1. Acesse o site como cliente (usuário WooCommerce comum)
2. Escolha uma cadeira disponível (status: Pronta)
3. Finalize o pedido (pedido entra como "Aguardando")
4. Após o pedido, o sistema exibirá o **Formulário de Empréstimo**
5. Após preenchido:
   - Status do pedido muda para **Emprestado**
   - Estoque reduz
   - Cadeira muda de status para **Emprestado**

---

## 🔁 5. Processo de Devolução

1. Cliente acessa o histórico e preenche o **Formulário de Devolução**
2. Status do pedido muda para **Devolvido**
3. Produto (cadeira) muda para **Em Avaliação**
4. É gerado automaticamente um **Formulário de Avaliação** para colaborador

---

## 🧪 6. Avaliação Interna (Colaborador)

1. Usuário com role `movliv_avaliador` acessa menu *Avaliações Pendentes*
2. Preenche o formulário com avaliação técnica
3. Resultado:
   - Aprovada → cadeira volta ao estoque e status = Pronta
   - Reprovada → cadeira marcada como Em Manutenção e novo formulário gerado

---

## 🔒 7. Regras de CPF

- Cada CPF só pode ter até **2 empréstimos ativos simultaneamente**
- Validação ocorre no checkout, bloqueando o envio se ultrapassado

---

## 📊 8. Relatórios

Acesse *Movimento Livre > Relatórios* para visualizar e exportar:
- Empréstimos por CPF
- Status de cadeiras
- Devoluções pendentes
- Histórico completo de avaliações

---

## 📬 9. Notificações

Por padrão, o plugin envia e-mails automáticos para:
- Confirmação de Empréstimo
- Lembrete de Devolução
- Confirmação de Devolução
- Alertas de Avaliação Interna

Você pode personalizar os templates em:
*Movimento Livre > E-mails*

---

## ✅ Pronto para uso!

O sistema está configurado para uso completo. Cadeiras disponíveis, formulários ativos, notificações habilitadas e controle de usuários definido.

Para expandir, consulte os arquivos:
- `EMAIL_TEMPLATES.md`
- `DOCUMENT_TEMPLATES.md`
- `PERMISSIONS.md`

Este guia pode ser entregue junto ao plugin como instrução rápida para administradores e técnicos do Instituto Bernardo Ferreira.

