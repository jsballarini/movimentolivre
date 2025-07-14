/**
 * JavaScript Frontend - Movimento Livre
 * @package MovimentoLivre
 * @since 0.0.1
 */

jQuery(document).ready(function($) {
    'use strict';

    // ==================== INICIALIZAÇÃO ==================== 
    
    var MovLivFrontend = {
        
        // Configurações
        config: {
            ajaxUrl: movliv_frontend.ajax_url,
            nonce: movliv_frontend.nonce,
            strings: movliv_frontend.strings || {}
        },

        // Inicialização
        init: function() {
            this.bindEvents();
            this.initForms();
            this.initMasks();
            this.initValidations();
        },

        // ==================== EVENTS ==================== 

        bindEvents: function() {
            // Submissão de formulários
            $(document).on('submit', '.movliv-form', this.handleFormSubmit.bind(this));
            
            // Validação em tempo real
            $(document).on('blur', '.movliv-form input[required]', this.validateField.bind(this));
            $(document).on('blur', '.movliv-form input[type="email"]', this.validateEmail.bind(this));
            $(document).on('blur', '.movliv-form input[name*="cpf"]', this.validateCPF.bind(this));
            $(document).on('blur', '.movliv-form input[name*="telefone"]', this.validatePhone.bind(this));
            
            // Busca e filtros
            $(document).on('submit', '.movliv-busca-form', this.handleSearch.bind(this));
            $(document).on('change', '.movliv-filtros select', this.handleFilter.bind(this));
            
            // Botões de ação
            $(document).on('click', '.movliv-btn[data-action]', this.handleButtonAction.bind(this));
            
            // Modais e popups
            $(document).on('click', '.open-modal', this.openModal.bind(this));
            $(document).on('click', '.close-modal, .modal-overlay', this.closeModal.bind(this));
            
            // Lazy loading para listas grandes
            $(window).on('scroll', this.debounce(this.handleLazyLoad.bind(this), 100));
        },

        // ==================== FORMULÁRIOS ==================== 

        initForms: function() {
            // Inicializa formulários específicos
            this.initEmprestimoForm();
            this.initDevolucaoForm();
            this.initAvaliacaoForm();
        },

        initEmprestimoForm: function() {
            var $form = $('#form-emprestimo');
            if ($form.length === 0) return;
            
            // Validação específica para empréstimo
            $form.find('input[name="cpf"]').on('blur', this.checkCPFLimits.bind(this));
            
            // Auto-preenchimento baseado em CEP
            $form.find('input[name="cep"]').on('blur', this.autoFillAddress.bind(this));
            
            // Verificação de disponibilidade da cadeira
            $form.find('select[name="cadeira_id"]').on('change', this.checkCadeiraDisponibilidade.bind(this));
        },

        initDevolucaoForm: function() {
            var $form = $('#form-devolucao');
            if ($form.length === 0) return;
            
            // Upload de fotos
            $form.find('input[type="file"]').on('change', this.handlePhotoUpload.bind(this));
            
            // Avaliação com estrelas
            $form.find('.rating-stars').on('click', '.star', this.handleStarRating.bind(this));
        },

        initAvaliacaoForm: function() {
            var $form = $('#form-avaliacao');
            if ($form.length === 0) return;
            
            // Checklist de avaliação
            $form.find('.checklist-item input[type="checkbox"]').on('change', this.updateChecklistStatus.bind(this));
            
            // Condicionais baseadas nas respostas
            $form.find('input[name="aprovado"]').on('change', this.handleApprovalChange.bind(this));
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(e.currentTarget);
            var formData = new FormData($form[0]);
            var self = this;
            
            // Validação final
            if (!this.validateForm($form)) {
                this.showAlert('error', 'Por favor, corrija os erros no formulário.');
                return;
            }
            
            // Mostra loading
            var $submitBtn = $form.find('[type="submit"]');
            var originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Enviando...');
            
            // Adiciona dados AJAX
            formData.append('action', $form.data('action') || 'movliv_submit_form');
            formData.append('nonce', self.config.nonce);
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showAlert('success', response.data.message || 'Formulário enviado com sucesso!');
                        
                        // Reset form ou redirect
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        } else {
                            $form[0].reset();
                        }
                    } else {
                        self.showAlert('error', response.data || 'Erro ao enviar formulário.');
                    }
                },
                error: function() {
                    self.showAlert('error', 'Erro de conexão. Tente novamente.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        // ==================== VALIDAÇÕES ==================== 

        initMasks: function() {
            // Máscaras para CPF
            $('input[name*="cpf"]').on('input', function() {
                var value = $(this).val().replace(/\D/g, '');
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                $(this).val(value);
            });
            
            // Máscaras para telefone
            $('input[name*="telefone"]').on('input', function() {
                var value = $(this).val().replace(/\D/g, '');
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                }
                $(this).val(value);
            });
            
            // Máscaras para CEP
            $('input[name*="cep"]').on('input', function() {
                var value = $(this).val().replace(/\D/g, '');
                value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
                $(this).val(value);
            });
        },

        initValidations: function() {
            // Configurações de validação personalizadas
            this.validationRules = {
                cpf: this.isValidCPF,
                email: this.isValidEmail,
                phone: this.isValidPhone,
                cep: this.isValidCEP
            };
        },

        validateForm: function($form) {
            var isValid = true;
            var self = this;
            
            $form.find('input[required], select[required], textarea[required]').each(function() {
                if (!self.validateField({ currentTarget: this })) {
                    isValid = false;
                }
            });
            
            return isValid;
        },

        validateField: function(e) {
            var $field = $(e.currentTarget);
            var value = $field.val().trim();
            var fieldName = $field.attr('name');
            var isValid = true;
            var errorMessage = '';
            
            // Validação required
            if ($field.attr('required') && !value) {
                isValid = false;
                errorMessage = 'Este campo é obrigatório.';
            }
            
            // Validações específicas
            if (value && fieldName) {
                if (fieldName.includes('cpf') && !this.isValidCPF(value)) {
                    isValid = false;
                    errorMessage = 'CPF inválido.';
                } else if (fieldName.includes('email') && !this.isValidEmail(value)) {
                    isValid = false;
                    errorMessage = 'Email inválido.';
                } else if (fieldName.includes('telefone') && !this.isValidPhone(value)) {
                    isValid = false;
                    errorMessage = 'Telefone inválido.';
                } else if (fieldName.includes('cep') && !this.isValidCEP(value)) {
                    isValid = false;
                    errorMessage = 'CEP inválido.';
                }
            }
            
            // Mostra/esconde erro
            this.showFieldError($field, isValid ? '' : errorMessage);
            
            return isValid;
        },

        validateCPF: function(e) {
            var $field = $(e.currentTarget);
            var cpf = $field.val().replace(/\D/g, '');
            
            if (!this.isValidCPF(cpf)) {
                this.showFieldError($field, 'CPF inválido.');
                return false;
            }
            
            this.showFieldError($field, '');
            return true;
        },

        validateEmail: function(e) {
            var $field = $(e.currentTarget);
            var email = $field.val();
            
            if (!this.isValidEmail(email)) {
                this.showFieldError($field, 'Email inválido.');
                return false;
            }
            
            this.showFieldError($field, '');
            return true;
        },

        validatePhone: function(e) {
            var $field = $(e.currentTarget);
            var phone = $field.val().replace(/\D/g, '');
            
            if (!this.isValidPhone(phone)) {
                this.showFieldError($field, 'Telefone inválido.');
                return false;
            }
            
            this.showFieldError($field, '');
            return true;
        },

        // ==================== VALIDADORES AUXILIARES ==================== 

        isValidCPF: function(cpf) {
            cpf = cpf.replace(/\D/g, '');
            
            if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) {
                return false;
            }
            
            var sum = 0;
            for (var i = 0; i < 9; i++) {
                sum += parseInt(cpf.charAt(i)) * (10 - i);
            }
            var digit1 = 11 - (sum % 11);
            if (digit1 >= 10) digit1 = 0;
            
            sum = 0;
            for (var i = 0; i < 10; i++) {
                sum += parseInt(cpf.charAt(i)) * (11 - i);
            }
            var digit2 = 11 - (sum % 11);
            if (digit2 >= 10) digit2 = 0;
            
            return digit1 == cpf.charAt(9) && digit2 == cpf.charAt(10);
        },

        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        isValidPhone: function(phone) {
            phone = phone.replace(/\D/g, '');
            return phone.length >= 10 && phone.length <= 11;
        },

        isValidCEP: function(cep) {
            cep = cep.replace(/\D/g, '');
            return cep.length === 8;
        },

        // ==================== FUNCIONALIDADES ESPECÍFICAS ==================== 

        checkCPFLimits: function(e) {
            var $field = $(e.currentTarget);
            var cpf = $field.val().replace(/\D/g, '');
            var self = this;
            
            if (!this.isValidCPF(cpf)) return;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'movliv_check_cpf_limits',
                    cpf: cpf,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.limitExceeded) {
                            self.showFieldError($field, 'CPF já possui o limite máximo de empréstimos ativos.');
                            $field.closest('form').find('[type="submit"]').prop('disabled', true);
                        } else {
                            self.showFieldError($field, '');
                            $field.closest('form').find('[type="submit"]').prop('disabled', false);
                        }
                    }
                }
            });
        },

        autoFillAddress: function(e) {
            var $field = $(e.currentTarget);
            var cep = $field.val().replace(/\D/g, '');
            var $form = $field.closest('form');
            
            if (!this.isValidCEP(cep)) return;
            
            // Busca CEP via API
            $.ajax({
                url: 'https://viacep.com.br/ws/' + cep + '/json/',
                type: 'GET',
                success: function(response) {
                    if (!response.erro) {
                        $form.find('input[name*="endereco"]').val(response.logradouro);
                        $form.find('input[name*="bairro"]').val(response.bairro);
                        $form.find('input[name*="cidade"]').val(response.localidade);
                        $form.find('select[name*="estado"]').val(response.uf);
                    }
                }
            });
        },

        checkCadeiraDisponibilidade: function(e) {
            var $select = $(e.currentTarget);
            var cadeiraId = $select.val();
            var self = this;
            
            if (!cadeiraId) return;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'movliv_check_cadeira_disponibilidade',
                    cadeira_id: cadeiraId,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (!response.data.disponivel) {
                            self.showAlert('warning', 'Esta cadeira não está disponível no momento.');
                            $select.val('');
                        }
                    }
                }
            });
        },

        // ==================== BUSCA E FILTROS ==================== 

        handleSearch: function(e) {
            e.preventDefault();
            
            var $form = $(e.currentTarget);
            var searchData = $form.serialize();
            var self = this;
            
            this.loadResults(searchData, $form.data('target') || '.movliv-results');
        },

        handleFilter: function(e) {
            var $container = $(e.currentTarget).closest('.movliv-filtros');
            var filterData = $container.find('select').serialize();
            
            this.loadResults(filterData, $container.data('target') || '.movliv-results');
        },

        loadResults: function(data, target) {
            var $target = $(target);
            var self = this;
            
            $target.html('<div class="movliv-loading"><div class="movliv-loading-spinner"></div><p>Carregando...</p></div>');
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: data + '&action=movliv_load_results&nonce=' + self.config.nonce,
                success: function(response) {
                    if (response.success) {
                        $target.html(response.data.html);
                    } else {
                        $target.html('<div class="movliv-empty-state"><h3>Nenhum resultado encontrado</h3></div>');
                    }
                },
                error: function() {
                    $target.html('<div class="movliv-alert alert-error">Erro ao carregar resultados.</div>');
                }
            });
        },

        // ==================== UTILITÁRIOS ==================== 

        showFieldError: function($field, message) {
            var $errorContainer = $field.siblings('.field-error');
            
            if (message) {
                if ($errorContainer.length === 0) {
                    $errorContainer = $('<div class="field-error"></div>');
                    $field.after($errorContainer);
                }
                $errorContainer.text(message).show();
                $field.addClass('error');
            } else {
                $errorContainer.hide();
                $field.removeClass('error');
            }
        },

        showAlert: function(type, message) {
            var $alert = $('<div class="movliv-alert alert-' + type + '">' + message + '</div>');
            
            // Remove alertas antigos
            $('.movliv-alert').remove();
            
            // Adiciona novo alerta
            if ($('.movliv-form').length) {
                $('.movliv-form').prepend($alert);
            } else {
                $('body').prepend($alert);
            }
            
            // Auto-remove após 5 segundos
            setTimeout(function() {
                $alert.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        handleButtonAction: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var action = $button.data('action');
            var self = this;
            
            switch(action) {
                case 'toggle-favorite':
                    this.toggleFavorite($button);
                    break;
                case 'share':
                    this.shareContent($button);
                    break;
                            default:
                // Ação não reconhecida
            }
        },

        handleLazyLoad: function() {
            var $lazyElements = $('.lazy-load:not(.loaded)');
            
            if ($lazyElements.length === 0) return;
            
            var windowBottom = $(window).scrollTop() + $(window).height();
            
            $lazyElements.each(function() {
                var $element = $(this);
                var elementTop = $element.offset().top;
                
                if (elementTop < windowBottom + 200) { // 200px antes
                    $element.addClass('loaded');
                    // Carrega conteúdo
                    this.loadLazyContent($element);
                }
            }.bind(this));
        },

        loadLazyContent: function($element) {
            var url = $element.data('url');
            if (!url) return;
            
            $.ajax({
                url: url,
                success: function(response) {
                    $element.html(response);
                },
                error: function() {
                    $element.html('<p>Erro ao carregar conteúdo.</p>');
                }
            });
        },

        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };

    // ==================== INICIALIZAÇÃO ==================== 

    MovLivFrontend.init();

    // ==================== COMPATIBILIDADE ==================== 

    // Expose algumas funções globalmente para compatibilidade
    window.MovLivFrontend = MovLivFrontend;
    
}); 