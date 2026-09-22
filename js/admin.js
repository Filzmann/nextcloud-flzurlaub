(function() {
    'use strict';
    const client = new window.LocalBase.api.ApiClient({ appId: 'adurlaub' });
    const confirmation = document.getElementById('adu-demo-confirm');
    const button = document.getElementById('adu-demo-install');
    const notice = document.getElementById('adu-demo-notice');
    if (!confirmation || !button || !notice) return;
    confirmation.addEventListener('change', () => { button.disabled = !confirmation.checked; });
    button.addEventListener('click', async () => {
        if (!confirmation.checked) return;
        button.disabled = true;
        notice.hidden = false;
        notice.className = 'adu-admin-notice';
        notice.textContent = 'Demo-Pack wird geprüft und installiert …';
        try {
            const response = await client.request('/api/admin/demo-pack/install', { method: 'POST', body: '{}' });
            notice.classList.add('is-success');
            notice.textContent = `${response.result.createdVacations} Urlaube angelegt; ${response.result.coveredGroups} Fachgruppen abgedeckt.`;
            confirmation.checked = false;
        } catch (error) {
            notice.classList.add('is-error');
            notice.textContent = error.message || 'Das Demo-Pack konnte nicht installiert werden.';
            button.disabled = false;
        }
    });
}());
