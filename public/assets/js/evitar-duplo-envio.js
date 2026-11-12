function travarBotoes(formulario) {
    formulario.setAttribute('aria-busy', 'true');
    for (const botao of formulario.querySelectorAll('button[type="submit"]')) {
        botao.disabled = true;
        if (botao.dataset.enviando) {
            botao.dataset.rotuloOriginal = botao.textContent;
            botao.textContent = botao.dataset.enviando;
        }
    }
}

function destravarBotoes() {
    for (const formulario of document.querySelectorAll('form[aria-busy="true"]')) {
        formulario.removeAttribute('aria-busy');
        for (const botao of formulario.querySelectorAll('button[type="submit"]')) {
            botao.disabled = false;
            if (botao.dataset.rotuloOriginal) {
                botao.textContent = botao.dataset.rotuloOriginal;
                delete botao.dataset.rotuloOriginal;
            }
        }
    }
}

window.addEventListener('submit', (evento) => {
    if (!evento.defaultPrevented && evento.target.method === 'post') {
        travarBotoes(evento.target);
    }
});

window.addEventListener('pageshow', (evento) => {
    if (evento.persisted) {
        destravarBotoes();
    }
});
