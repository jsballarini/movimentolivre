# 📚 EXEMPLOS_USO.md - Movimento Livre

Este documento apresenta exemplos práticos de uso dos shortcodes do plugin **Movimento Livre**, com cenários reais e casos de uso específicos.

---

## 🧪 **Shortcode de Avaliação: [movliv_form_avaliacao]**

### **Cenário 1: Lista de Avaliações Pendentes**

**Uso:** `[movliv_form_avaliacao]`

**Onde usar:** Página dedicada para colaboradores técnicos acessarem todas as cadeiras que precisam de avaliação.

**Resultado:**
- Lista todas as cadeiras com status "Em Avaliação" (devolvidas)
- Lista todas as cadeiras com status "Em Manutenção" (aguardando reavaliação)
- Botões diretos para iniciar avaliação de cada cadeira
- Interface organizada e responsiva
- **NOVO:** Mostra mensagem de sucesso quando avaliação é completada

**Exemplo de página:**
```
Título: Avaliações Técnicas Pendentes
Conteúdo: [movliv_form_avaliacao]
```

### **Cenário 2: Avaliação de Cadeira Específica**

**Uso:** `[movliv_form_avaliacao produto_id="123"]`

**Onde usar:** Após clicar em "Avaliar Cadeira" na lista, ou quando se conhece o ID específico.

**Resultado:**
- Formulário completo de avaliação técnica
- Checklist detalhado de componentes
- Campo para observações técnicas
- Resultado final (Aprovada/Reprovada)
- **NOVO:** Redirecionamento para a mesma página com mensagem de sucesso

### **Cenário 3: Pós-Avaliação (Automático)**

**Comportamento:**
- Após envio do formulário, usuário é redirecionado para `/avaliacoes-tecnicas?avaliacao_completed=1`
- A página detecta o parâmetro e exibe mensagem de sucesso
- Usuário permanece na mesma página, vendo a lista atualizada
- **Sem mais redirecionamentos para páginas administrativas!**

---

## 🔁 **Shortcode de Devolução: [movliv_form_devolucao]**

### **Cenário 1: Devolução de Pedido Específico**

**Uso:** `[movliv_form_devolucao pedido_id="456"]`

**Onde usar:** Página de devolução específica, link direto para um pedido.

**Resultado:**
- Formulário de devolução preenchido com dados do pedido
- Campos para observações e responsável
- Confirmação de devolução

### **Cenário 2: Lista de Cadeiras Emprestadas**

**Uso:** `[movliv_form_devolucao]`

**Onde usar:** Página onde usuários veem suas cadeiras emprestadas e escolhem qual devolver.

**Resultado:**
- **Usuários normais:** Veem apenas suas cadeiras emprestadas
- **Administradores:** Veem todas as cadeiras emprestadas no sistema
- Informações completas: TAG, modelo, datas, pedido
- Botão direto para iniciar devolução

---

## 🧾 **Shortcode de Empréstimo: [movliv_form_emprestimo]**

### **Cenário: Formulário de Retirada**

**Uso:** `[movliv_form_emprestimo pedido_id="789"]`

**Onde usar:** Página de confirmação após checkout, para usuário preencher dados de retirada.

**Resultado:**
- Formulário com dados do pedido pré-preenchidos
- Campos para responsável pelo atendimento
- Termos de responsabilidade
- Geração automática de PDF

---

## 🏠 **Páginas Recomendadas para Implementação**

### **1. Página de Avaliações Técnicas**
```
URL: /avaliacoes-tecnicas
Título: Avaliações Técnicas Pendentes
Conteúdo: [movliv_form_avaliacao]
Restrição: Apenas usuários com role movliv_colaborador ou superior
```

### **2. Página de Devolução**
```
URL: /devolver-cadeira
Título: Devolver Cadeira de Rodas
Conteúdo: [movliv_form_devolucao]
Restrição: Usuários logados
```

### **3. Página de Histórico do Usuário**
```
URL: /meus-emprestimos
Título: Meus Empréstimos
Conteúdo: [movliv_historico_cpf]
Restrição: Usuários logados
```

### **4. Página de Solicitação de Empréstimo**
```
URL: /solicitar-emprestimo
Título: Solicitar Empréstimo
Conteúdo: [movliv_form_emprestimo]
Restrição: Usuários logados
```

---

## 🔐 **Controle de Acesso por Role**

### **movliv_colaborador**
- ✅ Pode ver lista de cadeiras emprestadas
- ✅ Pode acessar formulário de devolução
- ✅ **NOVO:** Pode acessar lista de avaliações pendentes
- ✅ **NOVO:** Pode preencher formulários de avaliação
- ✅ **NOVO:** Pode alterar status de produtos
- ✅ **NOVO:** Pode fazer devoluções de cadeiras

### **movliv_avaliador**
- ✅ Pode fazer tudo que colaborador pode (mesmas permissões)
- ✅ **NOTA:** Role mantido para compatibilidade, mas permissões são idênticas ao colaborador

### **movliv_admin**
- ✅ Acesso total a todas as funcionalidades
- ✅ Pode ver todas as cadeiras emprestadas (não apenas suas)
- ✅ Pode gerenciar configurações do sistema

---

## 📱 **Exemplos de Interface**

### **Lista de Avaliações Pendentes (Sem produto_id)**
```
🔄 Cadeiras Devolvidas - Aguardando Avaliação
┌─────────┬──────────────┬─────────────────────┬─────────┐
│ TAG/SKU │   Modelo     │ Data da Devolução   │  Ação   │
├─────────┼──────────────┼─────────────────────┼─────────┤
│  CR001  │ Cadeira A    │ 10/01/2025 14:30   │ [Avaliar]│
│  CR002  │ Cadeira B    │ 09/01/2025 16:45   │ [Avaliar]│
└─────────┴──────────────┴─────────────────────┴─────────┘

🔧 Cadeiras em Manutenção - Aguardando Reavaliação
┌─────────┬──────────────┬─────────────────────────────┬─────────────┐
│ TAG/SKU │   Modelo     │ Data de Entrada Manutenção  │    Ação     │
├─────────┼──────────────┼─────────────────────────────┼─────────────┤
│  CR003  │ Cadeira C    │ 08/01/2025 10:15           │ [Reavaliar] │
└─────────┴──────────────┴─────────────────────────────┴─────────────┘
```

### **Lista de Cadeiras Emprestadas (Sem pedido_id)**
```
Minhas Cadeiras Emprestadas
┌─────────┬──────────────┬─────────────────────┬─────────────────────┬─────────┐
│ TAG/SKU │   Modelo     │ Data do Empréstimo │ Data Prevista Devol.│  Ação   │
├─────────┼──────────────┼─────────────────────┼─────────────────────┼─────────┤
│  CR001  │ Cadeira A    │ 05/01/2025         │ 15/01/2025          │[Devolver]│
└─────────┴──────────────┴─────────────────────┴─────────────────────┴─────────┘
```

---

## 🎯 **Casos de Uso Típicos**

### **Para Colaboradores Técnicos:**
1. Acessar `/avaliacoes-tecnicas`
2. Ver lista de cadeiras que precisam de avaliação
3. Clicar em "Avaliar Cadeira" para uma específica
4. Preencher formulário técnico
5. Marcar como aprovada ou enviar para manutenção

### **Para Usuários Finais:**
1. Acessar `/devolver-cadeira`
2. Ver lista de suas cadeiras emprestadas
3. Clicar em "Devolver Cadeira" para uma específica
4. Preencher formulário de devolução
5. Receber confirmação

### **Para Administradores:**
1. Acessar qualquer página do sistema
2. Ver todas as informações (não apenas suas)
3. Gerenciar configurações e usuários
4. Acessar relatórios e estatísticas

---

## 🚀 **Dicas de Implementação**

### **1. Organização de Páginas**
- Use URLs amigáveis e descritivas
- Agrupe funcionalidades relacionadas
- Mantenha hierarquia lógica de acesso

### **2. Controle de Acesso**
- Sempre verifique permissões antes de exibir conteúdo
- Use mensagens claras quando acesso for negado
- Redirecione usuários não autorizados

### **3. Experiência do Usuário**
- Mantenha interface consistente entre páginas
- Use botões e links claros e intuitivos
- Forneça feedback visual para ações realizadas

### **4. Responsividade**
- Teste em diferentes dispositivos
- Use CSS responsivo para tabelas
- Mantenha botões com tamanho adequado para mobile

---

Este documento serve como guia prático para implementação dos shortcodes em páginas reais do WordPress, garantindo a melhor experiência para todos os tipos de usuário.
