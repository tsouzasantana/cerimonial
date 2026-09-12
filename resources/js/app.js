

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
});

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
