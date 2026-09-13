

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Máscara de CPF/CNPJ e CEP nos campos marcados com data-mask.
function formatDocument(digits) {
    if (digits.length <= 11) {
        return digits
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    return digits
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
}

function formatCep(digits) {
    return digits.replace(/(\d{5})(\d{1,3})$/, '$1-$2');
}

function formatPhone(digits) {
    digits = digits.slice(0, 11);

    if (digits.length > 10) {
        return digits
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d)/, '$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{4})(\d)/, '$1-$2');
}

function syncCurrencyInput(displayInput) {
    const hidden = document.getElementById(displayInput.dataset.target);
    if (!hidden) return;

    const digits = displayInput.value.replace(/\D/g, '');

    if (digits === '') {
        displayInput.value = '';
        hidden.value = '';

        return;
    }

    const reais = (parseInt(digits, 10) / 100).toFixed(2);
    const [intPart, centPart] = reais.split('.');
    const withThousands = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    displayInput.value = `${withThousands},${centPart}`;
    hidden.value = reais;
}

document.addEventListener('input', (event) => {
    if (event.target.matches('[data-mask="document"]')) {
        const input = event.target;
        const digits = input.value.replace(/\D/g, '').slice(0, 14);
        input.value = formatDocument(digits);
    }

    if (event.target.matches('[data-mask="cep"]')) {
        const input = event.target;
        const digits = input.value.replace(/\D/g, '').slice(0, 8);
        input.value = formatCep(digits);
    }

    if (event.target.matches('[data-mask="phone"]')) {
        const input = event.target;
        const digits = input.value.replace(/\D/g, '').slice(0, 11);
        input.value = formatPhone(digits);
    }

    if (event.target.matches('[data-currency-input]')) {
        syncCurrencyInput(event.target);
    }
});

// Desabilita o botão de envio ao submeter um formulário, evitando duplo
// clique (ex.: gerar/enviar o PDF do contrato duas vezes). Formulários que
// abrem uma confirmação/modal antes de submeter (via preventDefault) não são
// afetados, pois o evento chega aqui com defaultPrevented = true.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.dataset.noLoading !== undefined) return;
    if (event.defaultPrevented) return;

    const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
    if (!submitter || submitter.disabled) return;

    submitter.disabled = true;
    submitter.classList.add('opacity-60', 'cursor-not-allowed');

    if (submitter.dataset.loadingText) {
        submitter.innerHTML = submitter.dataset.loadingText;
    }
});

// Confirma antes de submeter um <select> com auto-submit (usado no portal
// público, onde a troca de opção salva imediatamente, sem um botão
// "Salvar" separado). Se o usuário cancelar, o select volta ao valor
// original em vez de ficar mostrando uma opção que não foi salva.
window.confirmAndSubmit = function (select, message) {
    if (confirm(message)) {
        if (select.form.requestSubmit) {
            select.form.requestSubmit();
        } else {
            select.form.submit();
        }
    } else {
        select.value = select.dataset.originalValue;
    }
};

// Preenchimento automático de endereço a partir do CEP (API ViaCEP).
document.addEventListener('blur', (event) => {
    if (!event.target.matches('[data-cep-autofill]')) return;

    const cepInput = event.target;
    const statusEl = cepInput.closest('div')?.querySelector('.js-cep-status');
    const digits = cepInput.value.replace(/\D/g, '');

    if (statusEl) statusEl.textContent = '';
    if (digits.length !== 8) return;

    if (statusEl) statusEl.textContent = 'Buscando endereço...';

    fetch(`https://viacep.com.br/ws/${digits}/json/`)
        .then((response) => response.json())
        .then((data) => {
            if (data.erro) {
                if (statusEl) statusEl.textContent = 'CEP não encontrado.';
                return;
            }

            const setField = (id, value) => {
                const field = document.getElementById(id);
                if (field) field.value = value ?? '';
            };

            setField('address_street', data.logradouro);
            setField('address_district', data.bairro);
            setField('address_city', data.localidade);
            setField('address_state', data.uf);

            if (statusEl) statusEl.textContent = '';
            document.getElementById('address_number')?.focus();
        })
        .catch(() => {
            if (statusEl) statusEl.textContent = 'Não foi possível buscar o CEP agora.';
        });
}, true);
