const modal = document.querySelector('[data-abrir-ao-carregar]');

if (modal) {
    window.bootstrap.Modal.getOrCreateInstance(modal).show();
}
