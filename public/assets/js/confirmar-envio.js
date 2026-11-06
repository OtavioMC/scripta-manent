for (const formulario of document.querySelectorAll('form[data-confirmar]')) {
    formulario.addEventListener('submit', (evento) => {
        if (!window.confirm(formulario.dataset.confirmar)) {
            evento.preventDefault();
        }
    });
}
