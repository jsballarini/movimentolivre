/**
 * JavaScript para Forçar Cores Claras nos Formulários - Movimento Livre
 * Backup para garantir que as cores sejam aplicadas mesmo se o CSS falhar
 * @package MovimentoLivre
 * @since 0.0.1
 */

(function($) {
    'use strict';

    /**
     * Força cores claras em elementos de formulário
     */
    function forceLightColors() {
        // Seletores dos formulários
        const selectors = [
            '.movliv-form-container',
            '.movliv-form',
            '.movliv-form-group'
        ];

        selectors.forEach(function(selector) {
            const elements = document.querySelectorAll(selector);
            
            elements.forEach(function(element) {
                // Força cor de fundo branca
                element.style.setProperty('background-color', '#ffffff', 'important');
                element.style.setProperty('color', '#000000', 'important');
                
                // Aplica a todos os elementos filhos
                const children = element.querySelectorAll('*');
                children.forEach(function(child) {
                    // Pula elementos que devem manter cores específicas
                    if (child.classList.contains('button-primary') || 
                        child.classList.contains('btn-primary') ||
                        child.tagName === 'BUTTON') {
                        return;
                    }
                    
                    // Força cores em campos de entrada
                    if (child.tagName === 'INPUT' || 
                        child.tagName === 'SELECT' || 
                        child.tagName === 'TEXTAREA') {
                        child.style.setProperty('background-color', '#ffffff', 'important');
                        child.style.setProperty('color', '#000000', 'important');
                        child.style.setProperty('border-color', '#dddddd', 'important');
                    } else {
                        // Para outros elementos, força apenas a cor do texto
                        child.style.setProperty('color', '#000000', 'important');
                    }
                });
            });
        });
    }

    /**
     * Força cores em campos específicos
     */
    function forceInputColors() {
        const inputs = document.querySelectorAll('.movliv-form input, .movliv-form select, .movliv-form textarea, .movliv-form-group input, .movliv-form-group select, .movliv-form-group textarea');
        
        inputs.forEach(function(input) {
            input.style.setProperty('background-color', '#ffffff', 'important');
            input.style.setProperty('color', '#000000', 'important');
            input.style.setProperty('border-color', '#dddddd', 'important');
        });
    }

    /**
     * Força cores em labels e textos
     */
    function forceTextColors() {
        const textElements = document.querySelectorAll('.movliv-form label, .movliv-form span, .movliv-form p, .movliv-form div, .movliv-form h1, .movliv-form h2, .movliv-form h3, .movliv-form h4, .movliv-form h5, .movliv-form h6, .movliv-form-group label, .movliv-form-group span, .movliv-form-group p, .movliv-form-group div, .movliv-form-group h1, .movliv-form-group h2, .movliv-form-group h3, .movliv-form-group h4, .movliv-form-group h5, .movliv-form-group h6');
        
        textElements.forEach(function(element) {
            // Pula elementos que devem manter cores específicas
            if (element.classList.contains('button-primary') || 
                element.classList.contains('btn-primary')) {
                return;
            }
            
            element.style.setProperty('color', '#000000', 'important');
        });
    }

    /**
     * Força cores em seções especiais
     */
    function forceSectionColors() {
        // Seções de formulário
        const sections = document.querySelectorAll('.movliv-form .form-section');
        sections.forEach(function(section) {
            section.style.setProperty('background-color', '#f9f9f9', 'important');
            section.style.setProperty('color', '#000000', 'important');
        });

        // Informações do formulário
        const infoSections = document.querySelectorAll('.movliv-form .form-info');
        infoSections.forEach(function(info) {
            info.style.setProperty('background-color', '#f0f6fc', 'important');
            info.style.setProperty('color', '#000000', 'important');
        });

        // Termos e condições
        const termsSections = document.querySelectorAll('.movliv-form .form-terms');
        termsSections.forEach(function(terms) {
            terms.style.setProperty('background-color', '#fff3cd', 'important');
            terms.style.setProperty('color', '#000000', 'important');
        });
    }

    /**
     * Força cores em checkboxes e radio buttons
     */
    function forceCheckboxColors() {
        const checkboxes = document.querySelectorAll('.movliv-form input[type="checkbox"], .movliv-form input[type="radio"]');
        
        checkboxes.forEach(function(checkbox) {
            checkbox.style.setProperty('background-color', '#ffffff', 'important');
            checkbox.style.setProperty('border-color', '#dddddd', 'important');
        });
    }

    /**
     * Força cores em botões (mantendo cores específicas)
     */
    function forceButtonColors() {
        const buttons = document.querySelectorAll('.movliv-form .button-primary, .movliv-form .btn-primary, .movliv-form-group .button-primary, .movliv-form-group .btn-primary');
        
        buttons.forEach(function(button) {
            button.style.setProperty('background-color', '#007cba', 'important');
            button.style.setProperty('color', '#ffffff', 'important');
            button.style.setProperty('border-color', '#007cba', 'important');
        });
    }

    /**
     * Função principal para forçar todas as cores
     */
    function enforceAllColors() {
        forceLightColors();
        forceInputColors();
        forceTextColors();
        forceSectionColors();
        forceCheckboxColors();
        forceButtonColors();
    }

    /**
     * Observa mudanças no DOM para aplicar cores em elementos dinâmicos
     */
    function observeDOMChanges() {
        const observer = new MutationObserver(function(mutations) {
            let shouldEnforce = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.classList && (
                                node.classList.contains('movliv-form-container') ||
                                node.classList.contains('movliv-form') ||
                                node.classList.contains('movliv-form-group') ||
                                node.querySelector('.movliv-form-container') ||
                                node.querySelector('.movliv-form') ||
                                node.querySelector('.movliv-form-group')
                            )) {
                                shouldEnforce = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldEnforce) {
                // Aguarda um pouco para o DOM ser renderizado
                setTimeout(enforceAllColors, 100);
            }
        });

        // Observa mudanças em todo o documento
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Inicializa quando o DOM estiver pronto
     */
    $(document).ready(function() {
        // Aplica cores imediatamente
        enforceAllColors();
        
        // Observa mudanças no DOM
        observeDOMChanges();
        
        // Aplica cores periodicamente como backup
        setInterval(enforceAllColors, 5000);
        
        // Aplica cores em eventos específicos
        $(document).on('DOMNodeInserted', function(e) {
            if (e.target.classList && (
                e.target.classList.contains('movliv-form-container') ||
                e.target.classList.contains('movliv-form') ||
                e.target.classList.contains('movliv-form-group')
            )) {
                setTimeout(enforceAllColors, 100);
            }
        });
    });

    /**
     * Aplica cores quando a página é carregada
     */
    $(window).on('load', function() {
        enforceAllColors();
    });

    /**
     * Aplica cores quando a página é redimensionada
     */
    $(window).on('resize', function() {
        enforceAllColors();
    });

    /**
     * Aplica cores quando o scroll é feito
     */
    $(window).on('scroll', function() {
        enforceAllColors();
    });

    // Expõe função globalmente para uso externo
    window.MOVLIV_ForceColors = enforceAllColors;

})(jQuery);
