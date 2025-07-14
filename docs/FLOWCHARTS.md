# 🔄 FLOWCHARTS.md - Movimento Livre

Este documento apresenta os fluxogramas que representam os principais processos do plugin **Movimento Livre**, organizando visualmente as etapas de cada operação: empréstimo, devolução, avaliação e manutenção.

Os fluxogramas são descritos em texto estruturado e podem ser posteriormente convertidos para ferramentas visuais como diagrams.net, Whimsical, Figma, ou Mermaid.js.

---

## 📦 Fluxo 1: Solicitação de Empréstimo

```plaintext
Usuário acessa site
      ↓
Seleciona cadeira (produto com status = Pronta)
      ↓
Adiciona ao carrinho e finaliza pedido
      ↓
Pedido criado com status = Aguardando
      ↓
Usuário preenche Formulário de Empréstimo
      ↓
→ PDF gerado e salvo
→ Pedido atualizado para status = Emprestado
→ Produto atualizado para status = Emprestado
→ Estoque reduzido
```

---

## 🔁 Fluxo 2: Processo de Devolução

```plaintext
Usuário acessa seu histórico de empréstimos
      ↓
Seleciona pedido e clica em "Devolver Cadeira"
      ↓
Preenche Formulário de Devolução
      ↓
→ PDF gerado e salvo
→ Pedido atualizado para status = Devolvido
→ Produto atualizado para status = Em Avaliação
→ Gera formulário de avaliação interna
```

---

## 🧪 Fluxo 3: Avaliação Interna

```plaintext
Colaborador acessa lista de cadeiras com status = Em Avaliação
      ↓
Seleciona produto (cadeira) e preenche Formulário de Avaliação
      ↓
→ PDF gerado e salvo
→ Dados salvos no histórico do produto (avaliador, data, resultado)

  ↳ Se avaliação = Aprovada:
       → Produto status = Pronta
       → Produto retorna ao estoque

  ↳ Se avaliação = Reprovada:
       → Produto status = Em Manutenção
       → Gera novo formulário de avaliação após conserto
```

---

## 🔧 Fluxo 4: Reavaliação pós-Manutenção

```plaintext
Produto com status = Em Manutenção
      ↓
Colaborador preenche novo Formulário de Avaliação
      ↓
→ Histórico do produto atualizado com nova entrada

  ↳ Se Aprovada:
       → Produto status = Pronta
       → Retorna ao estoque

  ↳ Se Reprovada:
       → Permanece como Em Manutenção
       → Novo ciclo de manutenção e avaliação
```

---

## 🔒 Fluxo 5: Validação de CPF

```plaintext
Usuário tenta finalizar novo pedido
      ↓
Sistema verifica CPF nos pedidos com status = Aguardando ou Emprestado
      ↓
  ↳ Se total de empréstimos < 2:
       → Pedido permitido
  ↳ Se total ≥ 2:
       → Bloqueia pedido
       → Exibe mensagem informativa
```

---

## 🧠 Observações Finais

- Os fluxos foram projetados para garantir rastreabilidade e segurança
- Cada transição é acionada com base no preenchimento de formulários específicos
- Todos os fluxos suportam geração automática de documentos em PDF

Este documento serve como guia para entender o comportamento lógico do plugin e pode ser convertido para representação gráfica conforme necessário.

