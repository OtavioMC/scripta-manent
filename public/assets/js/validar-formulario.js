function marcarCampos(formulario) {
    for (const campo of formulario.querySelectorAll('input[name], select[name], textarea[name]')) {
        const erro = document.getElementById(`erro-${campo.name}`);
        const valido = campo.validity.valid;
        campo.classList.toggle('is-invalid', !valido);
        campo.toggleAttribute('aria-invalid', !valido);
        if (erro && !valido) {
            erro.textContent = campo.validity.valueMissing
                ? erro.dataset.mensagemObrigatorio
                : erro.dataset.mensagemFormato;
        }
    }
    formulario.querySelector(':invalid')?.focus();
}

for (const formulario of document.querySelectorAll('form[data-validar]')) {
    formulario.addEventListener('submit', (evento) => {
        if (!formulario.checkValidity()) {
            evento.preventDefault();
            marcarCampos(formulario);
        }
    });
}
