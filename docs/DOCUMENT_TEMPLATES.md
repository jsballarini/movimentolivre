# 📄 DOCUMENT_TEMPLATES.md - Movimento Livre

Este documento apresenta os modelos e elementos obrigatórios de cada formulário gerado em PDF pelo plugin **Movimento Livre**, garantindo padronização, validade legal e rastreabilidade em todos os processos de empréstimo.

---

## 📑 Formulário de Empréstimo (Retirada)

**Tipo:** Documento assinado pelo usuário no momento da retirada da cadeira.

**Vinculado ao:** Pedido (Empréstimo)

### Conteúdo obrigatório:
- Cabeçalho com logotipo do instituto e título "Termo de Empréstimo Gratuito"
- Nome completo do solicitante
- CPF do solicitante
- Telefone e endereço completo
- Data de retirada
- TAG/SKU da cadeira
- Termo de responsabilidade legal:
  - Compromisso com a devolução em boas condições
  - Prazo estimado para devolução
  - Responsabilidade por danos durante o uso
- Assinatura manual ou digital do solicitante
- Nome do responsável pelo atendimento (voluntário/colaborador)

---

## 🔁 Formulário de Devolução

**Tipo:** Documento assinado pelo solicitante ao devolver a cadeira.

**Vinculado ao:** Pedido (Empréstimo)

### Conteúdo obrigatório:
- Cabeçalho com logotipo e título "Comprovante de Devolução"
- Nome completo e CPF do solicitante
- TAG/SKU da cadeira devolvida
- Data da devolução
- Assinatura do responsável pela entrega
- Campo opcional para observações do solicitante
- Declaração de que a cadeira foi devolvida nas condições acordadas

---

## 🧪 Formulário de Avaliação Interna

**Tipo:** Documento técnico preenchido por colaborador após a devolução da cadeira.

**Vinculado ao:** Produto (Cadeira)

### Conteúdo obrigatório:
- Cabeçalho com título "Ficha Técnica de Avaliação da Cadeira"
- TAG/SKU da cadeira
- Nome do colaborador avaliador
- Data da avaliação
- Checklist com estado dos principais componentes:
  - Rodas, freios, estofamento, estrutura, encosto, apoios
- Campo para observações técnicas
- Resultado:
  - Aprovada para novo empréstimo (✅)
  - Reprovada – enviar para manutenção (❌)
- Assinatura do avaliador

---

## 🔧 Formulário de Reavaliação Pós-Manutenção

**Tipo:** Repetição do Formulário de Avaliação Interna, após manutenção.

**Vinculado ao:** Produto (Cadeira)

### Conteúdo obrigatório:
- Mesmo conteúdo da avaliação inicial
- Observação adicional: "Avaliação realizada após manutenção em [data]"
- Resultado final:
  - Aprovada e disponível para empréstimo
  - Reprovada (encaminhar para manutenção contínua)

---

## 🔐 Segurança dos Documentos

- Todos os formulários são gerados em **formato PDF** com carimbo de data
- Assinaturas podem ser:
  - Digitais (via formulário)
  - Manuais (em formulários impressos e digitalizados)
- Os documentos são armazenados em diretórios protegidos dentro de `/uploads/movliv/`
- Cada PDF é vinculado automaticamente ao Pedido ou Produto correspondente

---

## 🗂️ Padrão Visual (Estilo Sugerido)

- Fonte legível (ex: Arial, 11pt)
- Layout com duas colunas para campos técnicos
- Espaço reservado para carimbo ou logo institucional
- Identificação clara da função do documento (ex: "Comprovante de Retirada")

---

Este documento deve ser seguido por desenvolvedores e designers na criação e atualização dos templates PDF utilizados pelo plugin Movimento Livre.

