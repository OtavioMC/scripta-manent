const aviso = document.getElementById('aviso-de-alteracao');
const intervaloEmMilissegundos = 15000;

async function verificar() {
    try {
        const resposta = await fetch('/versoes', { headers: { Accept: 'application/json' } });
        if (!resposta.ok || !resposta.headers.get('Content-Type')?.includes('application/json')) {
            return;
        }
        const versoes = await resposta.json();
        const atual = versoes[aviso.dataset.colecao];
        if (atual !== undefined && String(atual) !== aviso.dataset.versao) {
            window.bootstrap.Toast.getOrCreateInstance(aviso, { autohide: false }).show();
            clearInterval(verificacao);
        }
    } catch {
        aviso.dataset.ultimaVerificacao = 'falhou';
    }
}

const verificacao = aviso ? setInterval(verificar, intervaloEmMilissegundos) : null;

aviso?.querySelector('[data-atualizar]')?.addEventListener('click', () => window.location.reload());
