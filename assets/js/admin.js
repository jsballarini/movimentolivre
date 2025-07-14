/**
 * JavaScript Admin - Movimento Livre
 * @package MovimentoLivre
 * @since 0.0.1
 */

jQuery(document).ready(function($) {
    'use strict';

    // ==================== INICIALIZAÇÃO ==================== 
    
    var MovLivAdmin = {
        
        // Configurações
        config: {
            ajaxUrl: movliv_admin.ajax_url,
            nonce: movliv_admin.nonce,
            strings: movliv_admin.strings
        },

        // Cache de elementos
        cache: {},

        // Inicialização
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initTooltips();
            this.loadDashboardStats();
        },

        // ==================== EVENTS ==================== 

        bindEvents: function() {
            // Dashboard stats refresh
            $(document).on('click', '.refresh-stats', this.loadDashboardStats.bind(this));
            
            // Status update para produtos
            $(document).on('click', '.update-product-status', this.updateProductStatus.bind(this));
            
            // Aprovação/reprovação de avaliações
            $(document).on('click', '.aprovar-cadeira', this.aprovarCadeira.bind(this));
            $(document).on('click', '.enviar-manutencao', this.enviarManutencao.bind(this));
            
            // Processamento de devoluções
            $(document).on('click', '.processar-devolucao', this.processarDevolucao.bind(this));
            
            // Exportação de relatórios
            $(document).on('click', '.export-report', this.exportReport.bind(this));
            
            // Filtros
            $(document).on('click', '#apply-filters', this.applyFilters.bind(this));
            $(document).on('change', '#filter-status', this.autoFilter.bind(this));
            
            // Busca
            $(document).on('keyup', '#search-cadeiras', this.debounce(this.searchCadeiras.bind(this), 300));
            
            // Navegação de relatórios
            $(document).on('click', '.nav-tab', this.switchReportTab.bind(this));
            
            // Confirmações
            $(document).on('click', '.confirm-action', this.confirmAction.bind(this));
        },

        // ==================== DASHBOARD ==================== 

        loadDashboardStats: function() {
            var self = this;
            
            // Mostra loading
            $('.movliv-stat-number').html('<div class="movliv-loading"></div>');
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'movliv_dashboard_stats',
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboardStats(response.data);
                    } else {
                        self.showNotification('error', self.config.strings.erro_generico);
                    }
                },
                error: function() {
                    self.showNotification('error', self.config.strings.erro_generico);
                }
            });
        },

        updateDashboardStats: function(stats) {
            $('.movliv-stat-card').each(function() {
                var $card = $(this);
                var statType = $card.data('stat-type');
                
                if (stats[statType] !== undefined) {
                    $card.find('.movliv-stat-number').text(stats[statType]);
                }
            });
            
            // Anima números
            this.animateNumbers();
        },

        animateNumbers: function() {
            $('.movliv-stat-number').each(function() {
                var $this = $(this);
                var countTo = parseInt($this.text()) || 0;
                
                $({ countNum: 0 }).animate({
                    countNum: countTo
                }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.countNum));
                    },
                    complete: function() {
                        $this.text(this.countNum);
                    }
                });
            });
        },

        // ==================== CHARTS ==================== 

        initCharts: function() {
            // Só inicializa gráficos se estão visíveis
            if ($('#dashboard').is(':visible')) {
                this.initEmprestimosChart();
                this.initStatusChart();
            }
            this.initPerformanceCharts();
        },

        initEmprestimosChart: function() {
            var canvas = document.getElementById('emprestimos-mensal-chart');
            if (!canvas) return;

            var self = this;
            
            // Destrói gráfico existente se houver
            var existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'movliv_generate_chart_data',
                    chart_type: 'emprestimos_mensal',
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        new Chart(canvas, {
                            type: 'line',
                            data: response.data,
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                aspectRatio: 2,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                onResize: function(chart, size) {
                                    // Evita loops de redimensionamento
                                    if (size.width > 0 && size.height > 0) {
                                        chart.resize();
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },

        initStatusChart: function() {
            var canvas = document.getElementById('status-cadeiras-chart');
            if (!canvas) return;

            var self = this;
            
            // Destrói gráfico existente se houver
            var existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'movliv_generate_chart_data',
                    chart_type: 'status_cadeiras',
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        new Chart(canvas, {
                            type: 'doughnut',
                            data: response.data,
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                aspectRatio: 1.5,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                },
                                onResize: function(chart, size) {
                                    // Evita loops de redimensionamento
                                    if (size.width > 0 && size.height > 0) {
                                        chart.resize();
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },

        initPerformanceCharts: function() {
            // Performance timeline chart
            var canvas = document.getElementById('performance-timeline-chart');
            if (!canvas) return;
            
            // Destrói gráfico existente se houver
            var existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }
            
            // Mock data para exemplo
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Eficiência (%)',
                        data: [85, 87, 89, 91, 88, 92],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.5,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    onResize: function(chart, size) {
                        // Evita loops de redimensionamento
                        if (size.width > 0 && size.height > 0) {
                            chart.resize();
                        }
                    }
                }
            });
        },

        // ==================== AÇÕES ESPECÍFICAS ==================== 

        updateProductStatus: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var productId = $button.data('product-id');
            var newStatus = $button.data('status');
            var self = this;
            
            $button.prop('disabled', true).text('Atualizando...');
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'movliv_update_product_status',
                    product_id: productId,
                    status: newStatus,
                    nonce: self.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification('success', response.data.message);
                        location.reload(); // Refresh página
                    } else {
                        self.showNotification('error', response.data);
                    }
                },
                error: function() {
                    self.showNotification('error', self.config.strings.erro_generico);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Atualizar');
                }
            });
        },

        aprovarCadeira: function(e) {
            e.preventDefault();
            
            var productId = $(e.currentTarget).data('product-id');
            this.updateProductStatus({
                currentTarget: $('<div>').data({
                    'product-id': productId,
                    'status': 'pronta'
                })[0],
                preventDefault: function() {}
            });
        },

        enviarManutencao: function(e) {
            e.preventDefault();
            
            var productId = $(e.currentTarget).data('product-id');
            this.updateProductStatus({
                currentTarget: $('<div>').data({
                    'product-id': productId,
                    'status': 'em_manutencao'
                })[0],
                preventDefault: function() {}
            });
        },

        processarDevolucao: function(e) {
            e.preventDefault();
            
            var orderId = $(e.currentTarget).data('order-id');
            var self = this;
            
            if (!confirm('Confirmar processamento da devolução?')) {
                return;
            }
            
            // TODO: Implementar AJAX para processar devolução
            self.showNotification('info', 'Funcionalidade em desenvolvimento');
        },

        // ==================== FILTROS E BUSCA ==================== 

        applyFilters: function(e) {
            e.preventDefault();
            
            var filters = {
                status: $('#filter-status').val(),
                search: $('#search-cadeiras').val()
            };
            
            this.loadFilteredResults(filters);
        },

        autoFilter: function() {
            this.applyFilters({ preventDefault: function() {} });
        },

        searchCadeiras: function() {
            this.applyFilters({ preventDefault: function() {} });
        },

        loadFilteredResults: function(filters) {
            var $container = $('#cadeiras-list');
            
            // Mostra loading
            $container.html('<div class="movliv-loading"><div class="movliv-loading-spinner"></div><p>Carregando...</p></div>');
            
            // TODO: Implementar AJAX para filtros
            setTimeout(function() {
                $container.html('<p>Resultados filtrados apareceriam aqui</p>');
            }, 1000);
        },

        // ==================== RELATÓRIOS ==================== 

        switchReportTab: function(e) {
            e.preventDefault();
            
            var $tab = $(e.currentTarget);
            var target = $tab.attr('href');
            
            // Remove active class e esconde seções
            $('.nav-tab').removeClass('nav-tab-active');
            $('.movliv-report-section').hide();
            
            // Ativa tab clicada e mostra seção
            $tab.addClass('nav-tab-active');
            $(target).show();
            
            // Re-inicializa gráficos se voltando para dashboard
            if (target === '#dashboard') {
                var self = this;
                setTimeout(function() {
                    self.initCharts();
                }, 100);
            }
            
            // Carrega dados específicos da tab se necessário
            this.loadTabData(target);
        },

        loadTabData: function(tabId) {
            var self = this;
            switch(tabId) {
                case '#performance':
                    // Delay para garantir que o container esteja visível
                    setTimeout(function() {
                        self.initPerformanceCharts();
                    }, 100);
                    break;
                // Adicionar outros casos conforme necessário
            }
        },

        exportReport: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var reportType = $button.data('report-type') || 'emprestimos';
            var self = this;
            
            $button.prop('disabled', true).text('Exportando...');
            
            // Cria form temporário para download
            var $form = $('<form>', {
                method: 'POST',
                action: self.config.ajaxUrl
            });
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'movliv_export_report'
            }));
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'type',
                value: reportType
            }));
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: self.config.nonce
            }));
            
            $('body').append($form);
            $form.submit();
            $form.remove();
            
            setTimeout(function() {
                $button.prop('disabled', false).text('Exportar CSV');
            }, 2000);
        },

        // ==================== UTILITÁRIOS ==================== 

        confirmAction: function(e) {
            var message = $(e.currentTarget).data('confirm') || self.config.strings.confirmar_exclusao;
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },

        showNotification: function(type, message) {
            var $notification = $('<div class="movliv-notification ' + type + '">' + message + '</div>');
            
            $('.wrap h1').after($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        initTooltips: function() {
            // Adiciona tooltips onde necessário
            $('[data-tooltip]').each(function() {
                var $this = $(this);
                var tooltip = $this.data('tooltip');
                
                $this.attr('title', tooltip);
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

    // ==================== FUNÇÕES GLOBAIS ==================== 

    // Funções expostas globalmente para uso em templates
    window.processarDevolucao = function(orderId) {
        MovLivAdmin.processarDevolucao({
            currentTarget: $('<div>').data('order-id', orderId)[0],
            preventDefault: function() {}
        });
    };

    window.aprovarCadeira = function(productId) {
        MovLivAdmin.aprovarCadeira({
            currentTarget: $('<div>').data('product-id', productId)[0],
            preventDefault: function() {}
        });
    };

    window.enviarManutencao = function(productId) {
        MovLivAdmin.enviarManutencao({
            currentTarget: $('<div>').data('product-id', productId)[0],
            preventDefault: function() {}
        });
    };

    window.exportarRelatorio = function(type) {
        MovLivAdmin.exportReport({
            currentTarget: $('<div>').data('report-type', type)[0],
            preventDefault: function() {}
        });
    };

    // ==================== INICIALIZAÇÃO ==================== 

    MovLivAdmin.init();

    // ==================== EXTRAS ==================== 

    // Auto-refresh stats a cada 5 minutos se estiver na página dashboard
    if (window.location.href.indexOf('movimento-livre') !== -1) {
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                MovLivAdmin.loadDashboardStats();
            }
        }, 300000); // 5 minutos
    }

    // Confirma antes de sair se houver mudanças não salvas
    var hasUnsavedChanges = false;
    
    $('input, select, textarea').on('change', function() {
        hasUnsavedChanges = true;
    });
    
    $('form').on('submit', function() {
        hasUnsavedChanges = false;
    });
    
    $(window).on('beforeunload', function() {
        if (hasUnsavedChanges) {
            return 'Você tem alterações não salvas. Tem certeza que deseja sair?';
        }
    });
}); 