document.addEventListener('submit', (evento) => {
    const mensagem = evento.target.dataset.confirmar;
    if (mensagem && !window.confirm(mensagem)) {
        evento.preventDefault();
    }
});
