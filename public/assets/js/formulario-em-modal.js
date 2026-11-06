const modal = document.getElementById('modal-formulario');

function preencher(formulario, campos) {
    for (const elemento of formulario.elements) {
        if (!elemento.name) {
            continue;
        }
        const nome = elemento.name.replace('[]', '');
        elemento.classList.remove('is-invalid');
        elemento.removeAttribute('aria-invalid');
        if (elemento.type === 'checkbox') {
            elemento.checked = (campos[nome] ?? []).map(String).includes(elemento.value);
        } else {
            elemento.value = campos[nome] ?? '';
        }
    }
}

if (modal) {
    const formulario = modal.querySelector('form');
    const titulo = modal.querySelector('.modal-title');

    for (const gatilho of document.querySelectorAll('[data-abrir-formulario]')) {
        gatilho.addEventListener('click', (evento) => {
            evento.preventDefault();
            formulario.action = gatilho.dataset.acao;
            titulo.textContent = gatilho.dataset.titulo;
            preencher(formulario, JSON.parse(gatilho.dataset.campos ?? '{}'));
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    }

    modal.addEventListener('shown.bs.modal', () => {
        formulario.querySelector('input:not([type="checkbox"])')?.focus();
    });
}
