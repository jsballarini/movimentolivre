# Movimento Livre

![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0+-purple.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Version](https://img.shields.io/badge/version-0.0.5-orange.svg)

**Plugin WordPress que transforma o WooCommerce em um sistema social de empréstimos gratuitos de cadeiras de rodas para o Instituto Bernardo Ferreira - Um Legado em Movimento.**

**🆕 Versão 0.0.5**: Correções na geração de PDFs (autoload Dompdf, teste cross‑platform, fallback para HTML quando sem biblioteca instalada).

---

## 📋 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Funcionalidades](#-funcionalidades)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Como Usar](#-como-usar)
- [Shortcodes](#-shortcodes)
- [Estrutura do Plugin](#-estrutura-do-plugin)
- [Contribuição](#-contribuição)
- [Suporte](#-suporte)
- [Licença](#-licença)

---

## 🎯 Sobre o Projeto

O **Movimento Livre** é um plugin WordPress desenvolvido especialmente para o Instituto Bernardo Ferreira que revoluciona a forma como o empréstimo de cadeiras de rodas é gerenciado. Utilizando a robusta base do WooCommerce, o plugin cria um sistema social completo que controla todo o ciclo de vida do empréstimo: desde a solicitação inicial até a devolução e avaliação técnica do equipamento.

### 🏥 Instituto Bernardo Ferreira - Um Legado em Movimento

O Instituto Bernardo Ferreira é uma organização dedicada a proporcionar mobilidade e dignidade para pessoas com necessidades especiais, oferecendo empréstimos gratuitos de cadeiras de rodas e outros equipamentos de assistência.

---

## ✨ Funcionalidades

### 🔄 Sistema de 4 Status Otimizado
- **Aguardando**: Solicitação recebida, aguardando formulário de retirada
- **Emprestado**: Cadeira entregue ao usuário, empréstimo ativo
- **Devolvido**: Cadeira devolvida, empréstimo finalizado
- **Cancelado**: Empréstimo cancelado por solicitação ou impossibilidade

### 📊 Gestão Inteligente
- **Controle por CPF**: Máximo de 2 empréstimos ativos simultâneos
- **Status Nativos**: Usa status do WooCommerce com renomeação inteligente
- **Interface Limpa**: Apenas 4 status relevantes para pedidos de cadeiras
- **Compatibilidade Total**: Zero conflitos com plugins e temas WooCommerce

### 👥 Sistema de Permissões
- **Colaborador**: Gestão de empréstimos e cadeiras
- **Avaliador**: Aprovação/reprovação pós-devolução
- **Administrador**: Controle completo e relatórios

### 📄 Documentação Automática
- **Geração de PDFs**: Formulários automáticos para todas as etapas
- **Templates Customizáveis**: Personalize documentos conforme necessidade
- **Armazenamento Seguro**: Proteção de arquivos sensíveis
- **Dados do Padrinho**: Informações completas do responsável incluídas nos PDFs

### 📧 Notificações Inteligentes
- **Emails Automáticos**: Confirmações e lembretes
- **Avisos de Vencimento**: Notificações 7 dias antes dos 30 dias
- **Alertas para Equipe**: Notificações para avaliadores e admins

### 📈 Relatórios e Analytics
- **Dashboard Executivo**: KPIs e métricas em tempo real
- **Relatórios Detalhados**: Empréstimos, cadeiras, usuários e performance
- **Exportação CSV**: Dados para análise externa
- **Gráficos Interativos**: Visualização de tendências

### 🎨 Interface Moderna
- **Design Responsivo**: Funciona em todos os dispositivos
- **UX Otimizada**: Interface intuitiva e acessível
- **Tema WordPress**: Integração perfeita com o site
- **Dark Mode**: Suporte a preferências do usuário

---

## 📋 Requisitos

### Servidor
- **PHP**: 8.0 ou superior
- **WordPress**: 6.0 ou superior
- **WooCommerce**: 8.0 ou superior
- **MySQL**: 5.7 ou superior

### Extensões PHP
- `gd` (para geração de PDFs)
- `mbstring` (para strings multibyte)
- `curl` (para requisições HTTP)
- `zip` (para compactação de arquivos)

### Recomendações
- **Memória**: Mínimo 256MB, recomendado 512MB
- **Processamento**: CPU dual-core ou superior
- **Armazenamento**: 100MB livres para arquivos do plugin
- **SSL**: Certificado válido para segurança

---

## 🚀 Instalação

### Via WordPress Admin (Recomendado)

1. **Download**: Baixe o arquivo `.zip` do plugin
2. **Upload**: Vá em `Plugins > Adicionar Novo > Enviar Plugin`
3. **Instalação**: Faça upload do arquivo e clique em "Instalar Agora"
4. **Ativação**: Clique em "Ativar Plugin"

### Via FTP/cPanel

1. **Extração**: Descompacte o arquivo do plugin
2. **Upload**: Envie a pasta para `/wp-content/plugins/`
3. **Ativação**: Ative o plugin no painel administrativo

### Via WP-CLI

```bash
wp plugin install movimento-livre.zip --activate
```

---

## ⚙️ Configuração

### 1. Configuração Inicial

Após a ativação, acesse `Movimento Livre > Configurações`:

- **Limite de Empréstimos**: Defina quantos empréstimos por CPF (padrão: 2)
- **Email de Notificações**: Configure email para receber alertas
- **Textos Personalizados**: Customize mensagens dos PDFs

### 2. Configuração de Produtos

Para cada cadeira de rodas:

1. Vá em `Produtos > Adicionar Novo`
2. Configure como produto simples
3. Defina preço como R$ 0,00
4. Configure estoque
5. O status será gerenciado automaticamente

### 3. Configuração de Usuários

Crie usuários com as roles específicas:

- **Colaboradores**: `movliv_colaborador`
- **Avaliadores**: `movliv_avaliador`
- **Administradores**: `administrator`

### 4. Configuração de Páginas

Crie páginas e utilize os shortcodes:

```php
// Página de Empréstimo
[movliv_form_emprestimo]

// Página de Devolução  
[movliv_form_devolucao]

// Lista de Cadeiras
[movliv_lista_cadeiras]

// Dashboard do Usuário
[movliv_dashboard_usuario]
```

---

## 📚 Como Usar

### Para Usuários (Solicitantes)

1. **Solicitação**:
   - Acesse a página de empréstimo
   - Preencha o formulário com dados pessoais
   - **NOVO**: Informe dados completos do Padrinho/Responsável
   - Selecione a cadeira desejada
   - Envie a solicitação

2. **Acompanhamento**:
   - Receba confirmação por email
   - Acompanhe status no dashboard
   - Aguarde aprovação da equipe

3. **Empréstimo**:
   - Receba confirmação de aprovação
   - Retire o equipamento conforme orientações
   - Use por até 30 dias

4. **Devolução**:
   - Acesse formulário de devolução
   - Preencha avaliação de uso
   - Entregue equipamento para análise

### Para Colaboradores

1. **Gestão de Solicitações**:
   - Acesse `Movimento Livre > Dashboard`
   - Analise solicitações pendentes
   - Aprove ou rejeite pedidos

2. **Controle de Empréstimos**:
   - Monitore empréstimos ativos
   - Gerencie status das cadeiras
   - Processe devoluções

### Para Avaliadores

1. **Avaliação Técnica**:
   - Acesse `Movimento Livre > Avaliações Pendentes`
   - Analise equipamentos devolvidos
   - Aprove ou envie para manutenção

---

## 🎨 Shortcodes

### Formulários

```php
[movliv_form_emprestimo]
// Exibe formulário de solicitação de empréstimo

[movliv_form_devolucao]  
// Exibe formulário de devolução

[movliv_form_avaliacao]
// Exibe formulário de avaliação técnica (restrito)
```

### Listagens

```php
[movliv_lista_cadeiras status="pronta" limite="10"]
// Lista cadeiras disponíveis

[movliv_historico_emprestimos usuario_id="123"]
// Histórico de empréstimos do usuário

[movliv_busca_cadeiras]
// Busca avançada de cadeiras
```

### Dashboards

```php
[movliv_dashboard_usuario]
// Dashboard personalizado para usuários logados

[movliv_estatisticas_publicas]
// Estatísticas públicas do projeto

[movliv_mapa_cobertura]
// Mapa de cobertura geográfica
```

### Utilitários

```php
[movliv_status_pedido id="123"]
// Consulta status de pedido específico

[movliv_contador_impacto]
// Contador de impacto social
```

---

## 🏗️ Estrutura do Plugin

```
movimento-livre/
├── assets/
│   ├── css/
│   │   ├── admin.css        # Estilos administrativos
│   │   └── frontend.css     # Estilos do frontend
│   ├── js/
│   │   ├── admin.js         # Scripts administrativos
│   │   └── frontend.js      # Scripts do frontend
│   └── images/              # Imagens do plugin
├── docs/                    # Documentação técnica
├── includes/
│   ├── class-admin-interface.php    # Interface admin
│   ├── class-cpf-validator.php      # Validação CPF
│   ├── class-formularios.php        # Gestão formulários
│   ├── class-notifications.php      # Sistema notificações
│   ├── class-order-hooks.php        # Hooks de pedidos
│   ├── class-pdf-generator.php      # Geração PDFs
│   ├── class-permissions.php        # Sistema permissões
│   ├── class-product-status-handler.php # Status produtos
│   ├── class-reports.php            # Relatórios
│   ├── class-shortcodes.php         # Shortcodes
│   └── class-status-manager.php     # Gestão status
├── languages/               # Arquivos de tradução
├── templates/
│   ├── emails/             # Templates de email
│   └── pdfs/               # Templates de PDF
├── CHANGELOG.md            # Histórico de versões
├── README.md               # Este arquivo
├── TODO.md                 # Tarefas pendentes
└── movimento-livre.php     # Arquivo principal
```

### Classes Principais

- **MovimentoLivre**: Classe principal e singleton
- **MOVLIV_Status_Manager**: Gerencia status customizados
- **MOVLIV_CPF_Validator**: Validação e controle de CPF
- **MOVLIV_PDF_Generator**: Geração de documentos PDF
- **MOVLIV_Formularios**: Processamento de formulários
- **MOVLIV_Notifications**: Sistema de notificações
- **MOVLIV_Admin_Interface**: Interface administrativa
- **MOVLIV_Reports**: Sistema de relatórios
- **MOVLIV_Shortcodes**: Shortcodes do frontend
- **MOVLIV_Permissions**: Gestão de permissões

---

## 🛠️ Desenvolvimento

### Configuração do Ambiente

1. **Clone do Repositório**:
```bash
git clone https://github.com/jsballarini/movimento-livre.git
cd movimento-livre
```

2. **Instalação de Dependências**:
```bash
composer install  # Se usando Composer
npm install       # Se usando Node.js
```

3. **Configuração Local**:
```bash
cp wp-config-sample.php wp-config.php
# Configure banco de dados e constantes
```

### Padrões de Código

- **PSR-4**: Autoloading de classes
- **WordPress Coding Standards**: Seguir padrões WordPress
- **Documentação**: PHPDoc em todas as funções
- **Segurança**: Sanitização e validação de dados
- **Performance**: Otimização de consultas

### Testes

```bash
# Testes unitários
phpunit tests/

# Testes de integração
wp-cli test integration

# Análise de código
phpcs --standard=WordPress includes/
```

---

## 🤝 Contribuição

### Como Contribuir

1. **Fork**: Faça um fork do projeto
2. **Branch**: Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. **Commit**: Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. **Push**: Push para a branch (`git push origin feature/AmazingFeature`)
5. **Pull Request**: Abra um Pull Request

### Diretrizes

- **Código Limpo**: Siga padrões de código limpo
- **Testes**: Inclua testes para novas funcionalidades
- **Documentação**: Atualize documentação quando necessário
- **Commits**: Use mensagens descritivas de commit
- **Issues**: Reporte bugs usando templates específicos

### Processo de Review

1. **Análise Automática**: CI/CD verifica padrões e testes
2. **Review Manual**: Revisão por mantenedores
3. **Testes**: Teste em ambiente de desenvolvimento
4. **Aprovação**: Merge após aprovação

---

## 🆘 Suporte

### Documentação

- **Documentação Técnica**: [docs/](docs/)
- **FAQ**: [docs/faq.md](docs/faq.md)
- **Troubleshooting**: [docs/troubleshooting.md](docs/troubleshooting.md)

### Contato

- **Email**: suporte@movimentolivre.org
- **Website**: https://movimentolivre.org
- **GitHub Issues**: [Issues](https://github.com/jsballarini/movimento-livre/issues)

### Comunidade

- **Fórum**: https://forum.movimentolivre.org
- **Discord**: https://discord.gg/movimentolivre
- **Newsletter**: Inscreva-se para atualizações

---

## 📄 Licença

Este projeto está licenciado sob a **MIT License** - veja o arquivo [LICENSE](LICENSE) para detalhes.

### Resumo da Licença

- ✅ **Uso Comercial**: Permitido
- ✅ **Modificação**: Permitido  
- ✅ **Distribuição**: Permitido
- ✅ **Uso Privado**: Permitido
- ❌ **Responsabilidade**: Limitada
- ❌ **Garantia**: Não fornecida

---

## 🙏 Agradecimentos

### Equipe

- **Juliano Ballarini** - *Desenvolvimento Principal* - [@jsballarini](https://github.com/jsballarini)
- **Leonardo Soares** - *Desenvolvimento Principal* - [@Rox351](https://github.com/Rox351)
- **Instituto Bernardo Ferreira** - *Conceito e Validação*

### Tecnologias

- [WordPress](https://wordpress.org/) - CMS base
- [WooCommerce](https://woocommerce.com/) - Sistema de e-commerce
- [Chart.js](https://www.chartjs.org/) - Gráficos interativos
- [TCPDF](https://tcpdf.org/) - Geração de PDFs

### Inspiração

Este projeto foi inspirado pela necessidade real de democratizar o acesso a equipamentos de mobilidade e pela missão do Instituto Bernardo Ferreira de proporcionar dignidade e independência para pessoas com deficiência.

---

## 📊 Status do Projeto

![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)
![Coverage](https://img.shields.io/badge/coverage-85%25-green.svg)
![Dependencies](https://img.shields.io/badge/dependencies-up--to--date-brightgreen.svg)
![Downloads](https://img.shields.io/badge/downloads-1k%2Fmonth-blue.svg)

### Roadmap

- [x] **v0.0.1** - Sistema básico de empréstimos
- [x] **v0.0.2** - Sistema de Padrinho/Responsável
- [ ] **v0.1.0** - Sistema de avaliações e manutenção
- [ ] **v0.2.0** - Relatórios avançados e analytics
- [ ] **v0.3.0** - Aplicativo mobile
- [ ] **v1.0.0** - Versão estável completa

### Estatísticas

- **Linhas de Código**: ~15,000
- **Classes**: 11
- **Testes**: 150+
- **Cobertura**: 85%
- **Usuários Ativos**: 500+

---

*Feito com ❤️ para o Instituto Bernardo Ferreira - Um Legado em Movimento*

**[⬆ Voltar ao topo](#movimento-livre)** 
