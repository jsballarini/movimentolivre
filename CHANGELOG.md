# 📋 CHANGELOG - Movimento Livre

## [0.0.8] - 2025-08-15

### ✨ Adicionado
- **Proteção por senha no shortcode `[movliv_lista_cadeiras]`**
  - Campo de configuração para definir senha no painel administrativo
  - Sistema de autenticação para usuários não-administradores
  - Acesso direto para administradores logados
  - Liberação automática quando campo de senha estiver vazio
  - Persistência de sessão após validação da senha

### 🔧 Melhorado
- Interface de configurações com campo de senha do shortcode
- Sistema de permissões mais robusto para controle de acesso

### 🐛 Corrigido
- Nenhuma correção nesta versão

### 📚 Documentação
- Atualização da documentação de configurações
- Guia de uso da proteção por senha

---

## [0.0.7] - 2025-10-15

### 🎨 **SUPORTE A TEMAS ESCUROS**
- **Compatibilidade com Tema Escuro**: Implementado sistema automático de detecção e aplicação de temas escuros
- **Variáveis CSS**: Sistema de variáveis CSS para centralizar e padronizar cores em todo o plugin
- **Detecção Automática**: Uso de `prefers-color-scheme: dark` para detectar preferências do usuário
- **Acessibilidade Melhorada**: Texto sempre legível independente do tema do sistema

### 🔧 **ARQUIVOS CSS ATUALIZADOS**
- **`assets/css/forms.css`**: Sistema completo de variáveis CSS com suporte a tema escuro
- **`assets/css/frontend.css`**: Padronização de cores e remoção de duplicações
- **Variáveis CSS**: 13 variáveis principais para cores de fundo, texto, bordas e botões
- **Fallbacks**: Suporte a navegadores que não suportam variáveis CSS

### 📱 **RESPONSIVIDADE E COMPATIBILIDADE**
- **Mobile First**: Tema escuro funciona perfeitamente em dispositivos móveis
- **Navegadores**: Compatível com Chrome 49+, Firefox 31+, Safari 9.1+, Edge 79+
- **Transições Suaves**: Mudanças de tema aplicadas instantaneamente
- **Sem JavaScript**: Funcionamento puramente via CSS

### 🎯 **ELEMENTOS AFETADOS**
- ✅ **Formulários**: Empréstimo, devolução e avaliação
- ✅ **Componentes**: Cards de cadeiras, dashboard, histórico
- ✅ **Interface**: Filtros, busca, alertas e mensagens
- ✅ **Acessibilidade**: Contraste adequado em ambos os temas

---

## [0.0.6] - 2025-01-13

### 🔧 **CORREÇÕES CRÍTICAS**
- **Status Inicial dos Pedidos**: Implementado sistema robusto para garantir que pedidos de empréstimo sempre entrem com status "Aguardando" (`on-hold`)
- **Prevenção de Mudanças Automáticas**: Adicionados hooks com prioridade máxima para interceptar e bloquear mudanças automáticas de status
- **Redução de Emails**: Sistema agora previne transições desnecessárias de status que causavam duplicação de emails

### ✨ **NOVAS FUNCIONALIDADES**
- **Hook `woocommerce_order_status`**: Intercepta status inicial com prioridade 999
- **Hook `woocommerce_new_order_status`**: Força status correto no momento da criação
- **Hook `woocommerce_order_status_changed`**: Previne mudanças automáticas com prioridade 1
- **Função `force_initial_loan_status()`**: Garante status "Aguardando" desde a criação
- **Função `prevent_automatic_status_changes()`**: Bloqueia mudanças automáticas sem formulário
- **Função `force_new_order_status()`**: Intercepta status no momento da criação
- **Função `ensure_loan_status_after_creation()`**: Garante status correto após criação

### 🛠️ **MELHORIAS TÉCNICAS**
- **Prioridade de Hooks**: Hooks críticos agora executam com prioridade máxima (999) e mínima (1)
- **Logs de Debug**: Sistema gera logs detalhados para rastreamento de mudanças de status
- **Controle de Formulários**: Sistema verifica se formulário foi enviado antes de permitir mudanças de status
- **Meta Fields**: Adicionados campos `_is_movimento_livre` e `_data_prevista_devolucao` automaticamente

### 📊 **FLUXO OTIMIZADO**
```
1. 🛒 Checkout → Status "Aguardando" (FORÇADO)
2. 📋 Formulário enviado → Status "Emprestado" (PERMITIDO)
3. 📝 Devolução enviada → Status "Devolvido" (PERMITIDO)
```

### 🎯 **RESULTADO ESPERADO**
- ✅ **Pedidos sempre entram como "Aguardando"**
- ✅ **Zero transições automáticas desnecessárias**
- ✅ **Redução significativa de emails duplicados**
- ✅ **Fluxo de empréstimo respeitado integralmente**

---

## [0.0.5] - 2025-08-13

### Corrigido
- Geração de PDFs falhando por ausência de autoload do Composer (Dompdf)
- Script de diagnóstico `test-pdf-generators.php` melhorado para detectar bibliotecas e comandos em Windows/Linux e testar Dompdf
- **CRÍTICO**: Sistema de emails enviando múltiplas notificações duplicadas (14 emails por transação)

### Adicionado
- Carregamento automático do `vendor/autoload.php` em múltiplos caminhos (plugin, wp-content, raiz do WP)
- Fallback claro para salvar como `.html` quando não houver biblioteca de PDF instalada
- Sistema de controle de duplicação de emails com meta fields `_movliv_formulario_processado` e `_movliv_formulario_devolucao_processado`

### Modificado
- `movimento-livre.php`: versão para 0.0.5 e carga de autoload
- `includes/class-pdf-generator.php`: detecção robusta de Dompdf e caminhos de autoload; fallback ajustado
- `test-pdf-generators.php`: criação de diretório, teste com Dompdf e detecção cross-platform de comandos
- `includes/class-formularios.php`: remoção de chamadas manuais duplicadas de notificações
- `includes/class-notifications.php`: controle de duplicação e desabilitação de emails nativos do WooCommerce

---

## [0.0.4] - 2025-08-10

### Corrigido
- Sistema de emails não funcionando nos formulários de empréstimo e devolução
- Integração direta entre formulários e sistema de notificações implementada
- Adicionados logs de debug detalhados na classe de notificações
- Melhorado tratamento de erros nos métodos de envio de email

### Adicionado
- Logs de debug detalhados para rastreamento de problemas de email
- Tratamento de exceções e erros fatais nos métodos de notificação
- Script de debug avançado para diagnóstico de problemas de email

### Arquivos Modificados
- `includes/class-formularios.php` - Integração direta com sistema de notificações
- `includes/class-notifications.php` - Logs de debug e tratamento de erros
- `debug-email-test.php` - Script de debug avançado
- `SOLUCAO_EMAILS_FORMULARIO.md` - Documentação da solução

## [0.0.3] - 2025-08-01

### Corrigido
- **Problema Crítico**: Dados do Padrinho e links de PDF não aparecendo no admin e área do usuário
  - Resolvido conflito de hooks entre `class-cpf-validator.php` e `class-order-hooks.php`
  - Consolidada exibição de dados do CPF e Padrinho em `display_order_extra_fields()`
  - Corrigido salvamento de PDFs em ambas as chaves meta para compatibilidade
  - Implementado salvamento completo de dados do Padrinho como meta do pedido
  - Adicionada função `format_cpf()` para formatação adequada de CPFs

- **Problema Crítico**: Formulário não redirecionando após envio
  - Corrigido callback de sucesso AJAX para forçar redirecionamento
  - Implementado fallback com `setTimeout` para garantir redirecionamento
  - Corrigido nome da ação AJAX para corresponder ao HTML

- **Problema Crítico**: Validação de campos de endereço incorreta
  - Atualizada validação para campos individuais (`rua`, `cidade`, `estado`, `cep`)
  - Corrigida reconstrução do campo `endereco` completo
  - Atualizada validação JavaScript para campos corretos

### Adicionado
- Logs de debug para rastrear dados do Padrinho
- Verificação de compatibilidade de meta keys para PDFs
- Função helper para formatação de CPF

### Modificado
- `includes/class-order-hooks.php` - Consolidação de exibição de dados
- `includes/class-formularios.php` - Correção de validação e salvamento
- `assets/js/forms.js` - Correção de redirecionamento e validação
- `includes/class-cpf-validator.php` - Remoção de hook conflitante

### Arquivos Modificados
- `includes/class-order-hooks.php`
- `includes/class-formularios.php`
- `assets/js/forms.js`
- `includes/class-cpf-validator.php`

## [0.0.2] - 2025-08-02

### Corrigido
- **Problema Crítico**: Formulários AJAX não funcionando
  - Criado arquivo `assets/js/forms.js` funcional
  - Implementado sistema de loading states e mensagens de sucesso/erro
  - Corrigido redirecionamento após envio do formulário

- **Problema Crítico**: Mapeamento incorreto de status nas notificações
  - Ajustado `custom_email_subject()` para usar status nativos (`on-hold`, `processing`, `completed`)
  - Corrigido `check_emprestimos_vencendo()` para usar status `processing` ao invés de `emprestado`

- **Problema Crítico**: Inconsistência de versões
  - Unificada versão para 0.0.2 em todos os arquivos de documentação

### Adicionado
- Sistema completo de formulários AJAX com validação
- Estados de loading e feedback visual
- Redirecionamento automático após envio
- Logs de debug para rastreamento

### Modificado
- `assets/js/forms.js` - Implementação completa
- `includes/class-notifications.php` - Correção de mapeamento de status
- `README.md`, `STATUS_DESENVOLVIMENTO.md`, `TODO.md` - Versão unificada

### Arquivos Modificados
- `assets/js/forms.js`
- `includes/class-notifications.php`
- `README.md`
- `STATUS_DESENVOLVIMENTO.md`
- `TODO.md`

## [0.0.1] - 2025-07-15

### Adicionado
- Sistema base do plugin Movimento Livre
- Integração com WooCommerce para empréstimos de cadeiras de rodas
- Sistema de status customizados para pedidos e produtos
- Validação de CPF e controle de limites de empréstimo
- Geração de PDFs para formulários de empréstimo, devolução e avaliação
- Interface administrativa para gestão de empréstimos
- Sistema de notificações por email
- Shortcodes para listagem de cadeiras e formulários
- Sistema de permissões e roles customizados
- Relatórios e dashboard administrativo
- Sistema de avaliação de cadeiras devolvidas

### Funcionalidades Principais
- Transformação do WooCommerce em sistema de empréstimos
- Gestão completa do ciclo de vida das cadeiras de rodas
- Controle de estoque e status de produtos
- Sistema de formulários com validação e geração de documentos
- Interface administrativa intuitiva
- Sistema de notificações automáticas
- Relatórios detalhados de empréstimos

### Arquivos Principais
- `movimento-livre.php` - Arquivo principal do plugin
- `includes/class-status-manager.php` - Gerenciador de status
- `includes/class-cpf-validator.php` - Validação de CPF
- `includes/class-pdf-generator.php` - Geração de PDFs
- `includes/class-formularios.php` - Sistema de formulários
- `includes/class-admin-interface.php` - Interface administrativa
- `includes/class-notifications.php` - Sistema de notificações
- `includes/class-shortcodes.php` - Shortcodes do frontend
- `assets/js/forms.js` - JavaScript dos formulários
- `assets/css/admin.css` - Estilos administrativos 
