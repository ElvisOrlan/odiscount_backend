const toast = document.querySelector('#toast');
let toastTimer;

function showToast(message) {
    toast.textContent = message;
    toast.classList.add('on');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('on'), 2200);
}

document.querySelectorAll('[data-category-toggle]').forEach((button) => {
    button.addEventListener('click', async () => {
        const nextActive = button.dataset.active !== '1';
        button.disabled = true;

        try {
            const response = await fetch(button.dataset.statusUrl, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ active: nextActive }),
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Impossible de modifier le statut de la catégorie.');
            }

            const active = result.status === 'active';
            button.dataset.active = active ? '1' : '0';
            button.setAttribute('aria-checked', String(active));
            button.setAttribute('aria-label', `${active ? 'Désactiver' : 'Activer'} la catégorie ${button.closest('tr').querySelector('.who b').textContent}`);
            button.closest('tr').querySelector('[data-category-status]').textContent = active ? 'Active' : 'Masquée';
            showToast(active ? 'Catégorie activée' : 'Catégorie désactivée');
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Erreur réseau : le statut n’a pas été modifié.');
        } finally {
            button.disabled = false;
        }
    });
});

document.querySelector('#category-filter')?.addEventListener('change', (event) => {
    event.target.form.requestSubmit();
});
