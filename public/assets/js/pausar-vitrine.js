const vitrine = document.getElementById('vitrine');
const botao = document.getElementById('pausar-vitrine');
const intervalo = Number(vitrine.dataset.bsInterval);

function definirRotacao(girando) {
    window.bootstrap.Carousel.getInstance(vitrine)?.dispose();
    const carrossel = new window.bootstrap.Carousel(vitrine, {
        ride: girando ? 'carousel' : false,
        interval: girando ? intervalo : false,
    });
    if (girando) {
        carrossel.cycle();
    }
    botao.textContent = girando ? 'Pausar' : 'Continuar';
    botao.dataset.girando = String(girando);
}

botao.addEventListener('click', () => {
    definirRotacao(botao.dataset.girando === 'false');
});

definirRotacao(!window.matchMedia('(prefers-reduced-motion: reduce)').matches);
