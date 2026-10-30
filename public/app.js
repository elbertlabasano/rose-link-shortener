document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    event.preventDefault();
    const toast = document.getElementById('toast');
    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        toast.textContent = 'Copied. Ready to share!';
    } catch {
        toast.textContent = 'Copy isn’t available here. Select the link and copy it manually.';
    }
    toast.hidden = false;
    clearTimeout(window.copyTimeout);
    window.copyTimeout = setTimeout(() => { toast.hidden = true; }, 3500);
});

document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});
