# 🔒 Proteção por Senha do Shortcode [movliv_lista_cadeiras]

## Visão Geral

A partir da versão **0.0.8**, o shortcode `[movliv_lista_cadeiras]` pode ser protegido por senha para controlar o acesso às informações das cadeiras disponíveis.

## Funcionalidades

### ✅ **Acesso Direto para Administradores**
- Usuários com role `administrator` ou `manage_woocommerce` têm acesso direto
- Não precisam digitar senha
- Visualizam a lista de cadeiras imediatamente

### 🔐 **Proteção por Senha para Usuários Comuns**
- Usuários não-administradores precisam digitar senha
- Senha configurável no painel administrativo
- Sessão persistente por 24 horas após validação

### 🚪 **Acesso Liberado sem Senha**
- Se nenhuma senha estiver configurada, o acesso é liberado
- Útil para ambientes de desenvolvimento ou uso público
- Configurável via painel administrativo

## Configuração

### 1. **Acessar Configurações**
```
WordPress Admin → Movimento Livre → Configurações
```

### 2. **Configurar Senha**
- Localizar campo "Senha do Shortcode Lista de Cadeiras"
- Digitar nova senha desejada
- Clicar em "Salvar Alterações"

### 3. **Remover Proteção**
- Deixar campo de senha em branco
- Clicar em "Salvar Alterações"
- Acesso será liberado automaticamente

## Como Funciona

### **Fluxo de Acesso**

```
Usuário acessa página com [movliv_lista_cadeiras]
                    ↓
        É administrador?
    ┌─────────┬─────────┐
    │   SIM   │   NÃO   │
    ↓         ↓         ↓
Acesso Direto    Senha configurada?
              ┌─────────┬─────────┐
              │   SIM   │   NÃO   │
              ↓         ↓         ↓
        Já autenticado?    Acesso Liberado
        ┌─────────┬─────────┐
        │   SIM   │   NÃO   │
        ↓         ↓         ↓
    Acesso Direto    Exibe Formulário
                    de Senha
```

### **Verificações de Segurança**

1. **Permissões de Usuário**
   - Verifica se é administrador
   - Verifica se tem permissão `manage_woocommerce`

2. **Autenticação por Senha**
   - Valida senha digitada
   - Compara com hash armazenado
   - Cria cookie seguro de autenticação

3. **Persistência de Sessão**
   - Cookie válido por 24 horas
   - Nonce WordPress para segurança
   - HTTPS obrigatório em produção

## Uso do Shortcode

### **Implementação Básica**
```php
[movliv_lista_cadeiras]
```

### **Páginas Recomendadas**
- Página de "Cadeiras Disponíveis"
- Página de "Solicitar Empréstimo"
- Página de "Catálogo"

## Interface do Usuário

### **Formulário de Senha**
- Design responsivo e moderno
- Mensagens de erro claras
- Dicas para o usuário
- Validação em tempo real

### **Estilos CSS**
- Cores consistentes com o tema
- Animações suaves
- Layout adaptável para mobile
- Acessibilidade otimizada

## Segurança

### **Proteções Implementadas**
- ✅ Hash de senha com `wp_hash_password()`
- ✅ Nonce WordPress para CSRF
- ✅ Sanitização de dados de entrada
- ✅ Cookies seguros (HttpOnly, Secure)
- ✅ Verificação de permissões
- ✅ Timeout de sessão (24h)

### **Boas Práticas**
- Use senhas fortes
- Altere senha periodicamente
- Monitore acessos
- Use HTTPS em produção

## Troubleshooting

### **Problemas Comuns**

#### **Senha não funciona**
- Verificar se foi salva corretamente
- Limpar cache do navegador
- Verificar permissões de usuário

#### **Acesso sempre pede senha**
- Verificar se cookie está sendo criado
- Verificar configurações de HTTPS
- Verificar domínio do cookie

#### **Administrador pede senha**
- Verificar role do usuário
- Verificar permissões `manage_woocommerce`
- Verificar se usuário está logado

### **Logs de Debug**
```php
// Adicionar ao wp-config.php para debug
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

## Exemplos de Uso

### **1. Página Protegida Simples**
```php
<!-- Página: Cadeiras Disponíveis -->
<h1>Cadeiras Disponíveis para Empréstimo</h1>
<p>Digite a senha para visualizar as cadeiras disponíveis:</p>

[movliv_lista_cadeiras]
```

### **2. Página com Múltiplos Shortcodes**
```php
<!-- Página: Dashboard de Empréstimos -->
<h1>Sistema de Empréstimos</h1>

<h2>Suas Cadeiras Emprestadas</h2>
[movliv_form_devolucao]

<h2>Cadeiras Disponíveis</h2>
[movliv_lista_cadeiras]
```

## Personalização

### **Modificar Estilos CSS**
```css
/* Personalizar cores do formulário */
.movliv-senha-container {
    background: #f8f9fa;
    border-color: #007bff;
}

.movliv-botao-senha .button {
    background: #28a745;
}
```

### **Modificar Mensagens**
```php
// No arquivo de traduções
__( '🔒 Acesso Protegido', 'movimento-livre' )
__( 'Esta área é protegida por senha...', 'movimento-livre' )
```

## Compatibilidade

### **Versões Suportadas**
- ✅ WordPress 6.0+
- ✅ WooCommerce 7.0+
- ✅ PHP 8.0+
- ✅ Navegadores modernos

### **Temas Testados**
- ✅ Twenty Twenty-Four
- ✅ Astra
- ✅ GeneratePress
- ✅ OceanWP
- ✅ Divi

## Suporte

### **Documentação Adicional**
- [README.md](../README.md) - Documentação principal
- [CHANGELOG.md](../CHANGELOG.md) - Histórico de versões
- [STATUS_DESENVOLVIMENTO.md](../STATUS_DESENVOLVIMENTO.md) - Status atual

### **Contato**
- **Desenvolvedor**: Juliano Ballarini
- **GitHub**: https://github.com/jsballarini
- **Plugin URI**: https://github.com/jsballarini

---

**Versão**: 0.0.8  
**Data**: 15/08/2025  
**Status**: ✅ Implementado e Testado
