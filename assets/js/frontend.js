jQuery(document).ready(function($) {
    // Formatação automática de CPF
    $('#padrinho_cpf').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        $(this).val(value);
    });

    // Formatação automática de CEP
    $('#padrinho_cep').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        $(this).val(value);
    });

    // Formatação automática de telefone
    $('#padrinho_telefone').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length <= 10) {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
        }
        $(this).val(value);
    });
});