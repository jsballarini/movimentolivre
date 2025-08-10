# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

## [0.0.4] - 2024-12-19

### Corrigido
- **Problema Crítico**: Pedidos sendo marcados como "Processando" antes do envio do formulário de empréstimo
  - Implementado sistema de interceptação de mudanças de status em tempo real
  - Adicionado hook `woocommerce_order_status_changed` com prioridade alta para bloquear mudanças indevidas
  - Modificada função `prevent_auto_processing_for_loans` para verificar existência de formulário
  - Implementada função `force_loan_status_immediate` para forçar status "Aguardando" imediatamente após criação
  - Adicionados logs de debug extensivos para rastrear fluxo de status
  - Corrigida prioridade do hook `woocommerce_checkout_order_processed` de 999 para 1
  - Implementada verificação dupla no Status Manager para bloquear mudanças indevidas

### Adicionado
- Sistema de interceptação de mudanças de status em tempo real
- Função `intercept_status_change` para bloquear mudanças para "processing" sem formulário
- Função `force_loan_status_immediate` para forçar status correto imediatamente
- Logs de debug extensivos em todas as funções de controle de status
- Arquivo `debug-status-flow.php` para diagnóstico do fluxo de status

### Modificado
- Prioridade do hook `woocommerce_checkout_order_processed` alterada de 999 para 1
- Função `force_loan_status` agora verifica existência de formulário antes de permitir mudança
- Função `prevent_auto_processing_for_loans` agora verifica existência de formulário
- Função `set_initial_loan_status` adicionados logs de debug detalhados
- Função `handle_order_status_change` no Status Manager agora bloqueia mudanças indevidas

### Arquivos Modificados
- `includes/class-order-hooks.php` - Implementação de interceptação de status
- `includes/class-status-manager.php` - Bloqueio adicional de mudanças indevidas
- `debug-status-flow.php` - Novo arquivo para diagnóstico

## [0.0.3] - 2024-12-19

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

## [0.0.2] - 2024-12-19

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

## [0.0.1] - 2024-12-19

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
