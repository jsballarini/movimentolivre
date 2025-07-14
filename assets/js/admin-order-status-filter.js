/**
 * Filtro de Status de Pedidos - Admin
 * Movimento Livre Plugin
 * 
 * Filtra status do WooCommerce para mostrar apenas os relevantes para empréstimos de cadeiras
 * Compatível com interface antiga (post.php) e nova (HPOS - admin.php?page=wc-orders)
 */

jQuery(document).ready(function($) {
    
    // Detecta qual interface estamos usando
    var isOldInterface = $('#post_type').val() === 'shop_order';
    var isNewInterface = window.location.href.indexOf('page=wc-orders') !== -1 && 
                        window.location.href.indexOf('action=edit') !== -1;
    
    if (!isOldInterface && !isNewInterface) {
        return;
    }
    
    // Para interface antiga, verifica se tem ID do pedido
    if (isOldInterface) {
        var postId = $('#post_ID').val();
        if (!postId) {
            return;
        }
        checkIfPluginOrder(postId);
    }
    
    // Para interface nova, extrai ID da URL
    if (isNewInterface) {
        var urlParams = new URLSearchParams(window.location.search);
        var orderId = urlParams.get('id');
        if (orderId) {
            checkIfPluginOrder(orderId);
        }
    }
    
    // Aplica filtro imediatamente
    filterOrderStatusesImmediate();
    
    // Observa mudanças no DOM
    observeStatusChanges();
    
    // Para Select2, aguarda inicialização e reaplica
    setTimeout(function() {
        handleSelect2StatusFilter();
    }, 1500);
    
    /**
     * Filtra os status IMEDIATAMENTE - abordagem mais robusta
     * Funciona com Select2 e interface normal
     */
    function filterOrderStatusesImmediate() {
        var $statusSelect = $('#order_status');
        if (!$statusSelect.length) {
            setTimeout(filterOrderStatusesImmediate, 500);
            return;
        }
        
        // Status que devem ser REMOVIDOS
        var unwantedStatuses = [
            'wc-pending',        // Pagamento Pendente
            'wc-refunded',       // Reembolsado
            'wc-failed',         // Malsucedido
            'wc-checkout-draft'  // Rascunho
        ];
        
        // Remove opções indesejadas do select original
        $statusSelect.find('option').each(function() {
            var optionValue = $(this).val();
            
            if (optionValue && unwantedStatuses.includes(optionValue)) {
                $(this).remove();
            }
        });
        
        // Renomeia labels dos status restantes
        renameStatusLabels();
        
        // Marca como filtrado
        $statusSelect.addClass('movliv-filtered-status');
        
        // Se for Select2, força atualização
        if ($statusSelect.hasClass('select2-hidden-accessible')) {
            $statusSelect.trigger('change.select2');
        }
    }
    
    /**
     * Manipula filtro específico para Select2
     */
    function handleSelect2StatusFilter() {
        var $statusSelect = $('#order_status');
        
        if (!$statusSelect.length || !$statusSelect.hasClass('select2-hidden-accessible')) {
            return;
        }
        
        // Intercepta abertura do Select2 para filtrar opções (APENAS para o campo de status)
        $statusSelect.on('select2:opening', function() {
            setTimeout(function() {
                filterSelect2Options();
            }, 10);
        });
        
        // Força recriação do Select2 com opções filtradas
        var currentValue = $statusSelect.val();
        $statusSelect.select2('destroy');
        filterOrderStatusesImmediate();
        
        // Recria Select2 APENAS para o campo de status
        $statusSelect.select2({
            width: '100%'
        });
        $statusSelect.val(currentValue).trigger('change');
    }
    
    /**
     * Filtra opções no dropdown do Select2 quando aberto
     * APENAS para o campo de status para evitar afetar outros Select2
     */
    function filterSelect2Options() {
        var unwantedStatuses = [
            'wc-pending',
            'wc-refunded', 
            'wc-failed',
            'wc-checkout-draft'
        ];
        
        // Aguarda o dropdown abrir e verifica se é do campo de status
        setTimeout(function() {
            // Verifica se o dropdown aberto é do campo order_status
            var $activeDropdown = $('.select2-dropdown');
            if (!$activeDropdown.length) {
                return;
            }
            
            // Verifica se o select ativo é o de status
            var $activeSelect = $('.select2-hidden-accessible:focus, #order_status');
            if (!$activeSelect.attr('id') || $activeSelect.attr('id') !== 'order_status') {
                return;
            }
            
            // Filtra opções APENAS no dropdown do Select2 de status
            $activeDropdown.find('.select2-results__option').each(function() {
                var $option = $(this);
                var optionValue = $option.attr('data-select2-id');
                
                if (optionValue && unwantedStatuses.some(status => optionValue.includes(status))) {
                    $option.remove();
                }
            });
        }, 50);
    }
    
    /**
     * Verifica se o pedido é do plugin (para logs e debug)
     */
    function checkIfPluginOrder(orderId) {
        // Verifica se existe o objeto AJAX
        if (typeof movliv_admin_order_status_filter === 'undefined') {
            return;
        }
        
        $.ajax({
            url: movliv_admin_order_status_filter.ajax_url,
            type: 'POST',
            data: {
                action: 'movliv_check_plugin_order',
                order_id: orderId,
                nonce: movliv_admin_order_status_filter.nonce
            },
            success: function(response) {
                // Filtro aplicado independentemente
            },
            error: function(xhr, status, error) {
                // Filtro aplicado independentemente
            }
        });
    }
    
    /**
     * Renomeia labels dos status para o contexto de empréstimos
     */
    function renameStatusLabels() {
        var $statusSelect = $('#order_status');
        if (!$statusSelect.length) {
            return;
        }
        
        // Renomeia as opções do select
        $statusSelect.find('option').each(function() {
            var $option = $(this);
            var optionValue = $option.val();
            
            switch(optionValue) {
                case 'wc-processing':
                    $option.text('Emprestado');
                    break;
                case 'wc-completed':
                    $option.text('Devolvido');
                    break;
            }
        });
        
        // Se for Select2, atualiza o texto exibido também (APENAS para o campo de status)
        if ($statusSelect.hasClass('select2-hidden-accessible')) {
            var currentValue = $statusSelect.val();
            var currentText = $statusSelect.find('option:selected').text();
            
            // Atualiza o texto exibido APENAS no Select2 do campo de status
            // Seletor específico para evitar afetar outros Select2 da página
            $statusSelect.next('.select2-container').find('.select2-selection__rendered').text(currentText);
        }
    }
    
    /**
     * Adiciona informação visual sobre os status
     */
    function addStatusInfo() {
        if (!$('.movliv-status-info').length) {
            $('#order_status').closest('.form-field, .form-row').after(
                '<p class="movliv-status-info" style="color: #0073aa; font-size: 11px; margin-top: 5px;">' +
                '<span class="dashicons dashicons-info"></span> ' +
                'Fluxo: Aguardando → Emprestado → Devolvido (+ Cancelado)' +
                '</p>'
            );
        }
    }
    /**
     * Observa mudanças no DOM para manter o filtro ativo
     */
    function observeStatusChanges() {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    var $statusSelect = $('#order_status');
                    if ($statusSelect.length && !$statusSelect.hasClass('movliv-filtered-status')) {
                        setTimeout(filterOrderStatusesImmediate, 100);
                    }
                }
            });
        });
        
        var targetNode = document.querySelector('#post-body, .wrap, .woocommerce-layout__main') || document.body;
        observer.observe(targetNode, { childList: true, subtree: true });
    }
    
    // Eventos para interface nova (HPOS)
    if (isNewInterface) {
        // Aguarda carregamento completo da interface nova
        $(window).on('load', function() {
            setTimeout(function() {
                filterOrderStatusesImmediate();
                handleSelect2StatusFilter();
                // addStatusInfo();
            }, 2000);
        });
        
        // Reaplica filtro quando AJAX da interface nova termina
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url && settings.url.indexOf('wc-orders') !== -1) {
                setTimeout(function() {
                    filterOrderStatusesImmediate();
                    handleSelect2StatusFilter();
                }, 500);
            }
        });
    }
    
    // Eventos para interface antiga
    if (isOldInterface) {
        $(window).on('load', function() {
            setTimeout(function() {
                filterOrderStatusesImmediate();
                // addStatusInfo();
            }, 1000);
        });
    }
    
}); 