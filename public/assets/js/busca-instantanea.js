const formulario = document.querySelector('form.busca');
const esperaEmMilissegundos = 300;
let pedidoEmAndamento = null;

async function atualizarResultados() {
    const endereco = new URL(formulario.action, window.location.origin);
    const termo = formulario.elements.busca.value.trim();
    if (termo !== '') {
        endereco.searchParams.set('busca', termo);
    }

    pedidoEmAndamento?.abort();
    pedidoEmAndamento = new AbortController();

    let html;
    try {
        const resposta = await fetch(endereco, {
            headers: { Accept: 'text/html' },
            signal: pedidoEmAndamento.signal,
        });
        if (!resposta.ok) {
            return;
        }
        html = await resposta.text();
    } catch (erro) {
        if (erro.name === 'AbortError') {
            return;
        }
        throw erro;
    }

    const pagina = new DOMParser().parseFromString(html, 'text/html');
    const novos = pagina.querySelector('[data-resultados]');
    const atuais = document.querySelector('[data-resultados]');
    if (novos && atuais) {
        atuais.replaceChildren(...novos.childNodes);
        window.history.replaceState(null, '', endereco);
        for (const link of document.querySelectorAll('[data-mantem-busca]')) {
            link.href = link.dataset.mantemBusca + endereco.search;
        }
    }
}

if (formulario) {
    let espera = null;
    formulario.elements.busca.addEventListener('input', () => {
        clearTimeout(espera);
        espera = setTimeout(atualizarResultados, esperaEmMilissegundos);
    });
}
