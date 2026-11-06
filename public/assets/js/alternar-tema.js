const raiz = document.documentElement;
const botao = document.getElementById('alternar-tema');

function mostrarProximoTema() {
    botao.textContent = raiz.dataset.bsTheme === 'dark' ? 'Tema claro' : 'Tema escuro';
}

botao.addEventListener('click', () => {
    raiz.dataset.bsTheme = raiz.dataset.bsTheme === 'dark' ? 'light' : 'dark';
    try {
        localStorage.setItem('tema', raiz.dataset.bsTheme);
    } catch {
        botao.dataset.temaNaoSalvo = 'sim';
    }
    mostrarProximoTema();
});

mostrarProximoTema();
