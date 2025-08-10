/**
 * JavaScript de Formulários (Frontend) - Movimento Livre
 * Responsável por enviar os formulários via AJAX, exibir mensagens e controlar estados de loading
 * Depende de jQuery e do objeto global movliv_ajax (localizado em PHP)
 */

(function($) {
  'use strict';

  function showNotice($form, type, message) {
    var $notice = $form.find('.movliv-form-notice');
    if ($notice.length === 0) {
      $notice = $('<div class="movliv-form-notice" />').prependTo($form);
    }
    $notice
      .removeClass('is-success is-error')
      .addClass(type === 'success' ? 'is-success' : 'is-error')
      .html(message || '');
  }

  function setLoading($form, isLoading) {
    var $btn = $form.find('button[type="submit"]');
    if (isLoading) {
      $btn.data('original-text', $btn.text());
      $btn.prop('disabled', true).text('Enviando...');
      $form.addClass('is-submitting');
    } else {
      $btn.prop('disabled', false).text($btn.data('original-text') || 'Enviar');
      $form.removeClass('is-submitting');
    }
  }

  function ensureNonceOnData($form, data) {
    // Garante envio do nonce esperado pelo servidor se não estiver no form
    var hasNonce = $form.find('input[name="nonce"]').length > 0;
    if (!hasNonce && window.movliv_ajax && movliv_ajax.nonce) {
      data.push({ name: 'nonce', value: movliv_ajax.nonce });
    }
    return data;
  }

  function handleAjaxSubmit($form, extraValidationFn) {
    $form.on('submit', function(e) {
      e.preventDefault();

      // Validação adicional opcional
      if (typeof extraValidationFn === 'function') {
        var validation = extraValidationFn($form);
        if (validation && validation.error) {
          showNotice($form, 'is-error', validation.message || (movliv_ajax && movliv_ajax.messages && movliv_ajax.messages.required) || 'Preencha os campos obrigatórios.');
          return;
        }
      }

      var data = $form.serializeArray();
      data = ensureNonceOnData($form, data);

      setLoading($form, true);
      showNotice($form, 'is-error', '');

      $.ajax({
        url: (window.movliv_ajax && movliv_ajax.ajax_url) || (window.ajaxurl || ''),
        type: 'POST',
        data: $.param(data),
        dataType: 'json'
      }).done(function(response) {
        if (response && response.success) {
          var msg = (response.data && response.data.message) || (movliv_ajax && movliv_ajax.messages && movliv_ajax.messages.success) || 'Formulário enviado com sucesso!';
          showNotice($form, 'success', msg);

          if (response.data && response.data.redirect) {
            // Redireciona imediatamente; fallback após 1.5s se popup ou bloqueio
            try { window.location.assign(response.data.redirect); } catch(e) {}
            setTimeout(function() { window.location.href = response.data.redirect; }, 1500);
          }
        } else {
          var err = (response && response.data) || (movliv_ajax && movliv_ajax.messages && movliv_ajax.messages.error) || 'Erro ao enviar formulário. Tente novamente.';
          showNotice($form, 'error', err);
        }
      }).fail(function() {
        var err = (movliv_ajax && movliv_ajax.messages && movliv_ajax.messages.error) || 'Erro ao enviar formulário. Tente novamente.';
        showNotice($form, 'error', err);
      }).always(function() {
        setLoading($form, false);
      });
    });
  }

  $(function() {
    // Empréstimo
    var $emprestimoForm = $('#movliv-emprestimo-form');
    if ($emprestimoForm.length) {
      // ✅ CORREÇÃO: Define a ação correta para o formulário
      $emprestimoForm.find('input[name="action"]').val('movliv_submit_emprestimo');
      
      handleAjaxSubmit($emprestimoForm, function($f) {
        // ✅ CORREÇÃO: Campos obrigatórios atualizados para corresponder ao HTML
        var requiredIds = [
          '#data_prevista_devolucao',
          '#responsavel_atendimento',
          '#padrinho_nome',
          '#padrinho_cpf',
          '#padrinho_endereco',
          '#padrinho_numero',
          '#padrinho_cidade',
          '#padrinho_estado',
          '#padrinho_cep',
          '#padrinho_telefone'
        ];
        for (var i = 0; i < requiredIds.length; i++) {
          var $el = $f.find(requiredIds[i]);
          if ($el.length && !$el.val()) {
            return { error: true, message: (movliv_ajax && movliv_ajax.messages && movliv_ajax.messages.required) || 'Por favor, preencha todos os campos obrigatórios.' };
          }
        }
        // Aceite de termos
        var $terms = $f.find('input[name="aceita_termos"]');
        if ($terms.length && !$terms.is(':checked')) {
          return { error: true, message: 'É necessário aceitar os termos de responsabilidade.' };
        }
        return { error: false };
      });
    }

    // Devolução
    var $devolucaoForm = $('#movliv-devolucao-form');
    if ($devolucaoForm.length) {
      handleAjaxSubmit($devolucaoForm, function($f) {
        var requiredIds = ['#nome', '#responsavel_devolucao'];
        for (var i = 0; i < requiredIds.length; i++) {
          var $el = $f.find(requiredIds[i]);
          if ($el.length && !$el.val()) {
            return { error: true };
          }
        }
        return { error: false };
      });
    }

    // Avaliação
    var $avaliacaoForm = $('#movliv-avaliacao-form');
    if ($avaliacaoForm.length) {
      handleAjaxSubmit($avaliacaoForm, function($f) {
        var requiredIds = ['#avaliador', '#resultado'];
        for (var i = 0; i < requiredIds.length; i++) {
          var $el = $f.find(requiredIds[i]);
          if ($el.length && !$el.val()) {
            return { error: true };
          }
        }
        return { error: false };
      });
    }
  });

})(jQuery);


