(() => {
    let salvo = null;
    try {
        salvo = localStorage.getItem('tema');
    } catch {
        salvo = null;
    }
    const escuroNoSistema = window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.dataset.bsTheme = salvo ?? (escuroNoSistema ? 'dark' : 'light');
})();
