// Mejora progresiva: los formularios y las vistas funcionan sin JavaScript.
document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const button = event.target.closest('[data-dismiss-alert]');

    if (button) {
        button.closest('[role="status"]')?.remove();
    }
});
