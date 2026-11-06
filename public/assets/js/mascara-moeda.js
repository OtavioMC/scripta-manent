function formatarMoeda(texto) {
    const digitos = texto.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
    if (digitos === '') {
        return '';
    }

    const centavos = digitos.padStart(3, '0');
    const inteiro = centavos.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return `${inteiro},${centavos.slice(-2)}`;
}

for (const campo of document.querySelectorAll('input[data-mascara="moeda"]')) {
    campo.addEventListener('input', () => {
        campo.value = formatarMoeda(campo.value);
    });
}
