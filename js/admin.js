(function() {
    'use strict';
    const client = new window.LocalBase.api.ApiClient({ appId: 'adurlaub' });
    const form = document.getElementById('adu-full-access-form');
    const history = document.getElementById('adu-full-access-history');
    const fullStatus = document.getElementById('adu-full-access-status');
    const show = (message, error = false) => { fullStatus.textContent = message; fullStatus.className = error ? 'is-error' : 'is-success'; };
    const date = value => value ? new Date(value).toLocaleString() : '—';
    const loadFullAccess = async () => {
        if (!history) return;
        try {
            const state = await client.request('/api/admin/full-access'); history.replaceChildren();
            if (!state.history?.length) { const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 6; cell.textContent = 'Noch keine Freigaben protokolliert.'; row.append(cell); history.append(row); return; }
            state.history.forEach(grant => { const row = document.createElement('tr'); const active = !grant.revokedAt && new Date(grant.startsAt).getTime() <= Date.now() && new Date(grant.endsAt).getTime() > Date.now(); [grant.targetUid,grant.grantedBy,date(grant.startsAt),date(grant.endsAt),grant.revokedAt?date(grant.revokedAt):(active?'Aktiv':'Planmäßig beendet')].forEach(value=>{const cell=document.createElement('td');cell.textContent=value;row.append(cell);}); const action=document.createElement('td'); if(active){const button=document.createElement('button');button.type='button';button.textContent='Widerrufen';button.dataset.revokeUid=grant.targetUid;action.append(button);} row.append(action);history.append(row); });
        } catch (error) { show(error.message || 'Die Vollzugriffshistorie konnte nicht geladen werden.', true); }
    };
    form?.addEventListener('submit', async event => { event.preventDefault(); const fields=new FormData(form); if(fields.get('enabled')!=='on')return; try{await client.request('/api/admin/full-access',{method:'POST',body:JSON.stringify({targetUid:String(fields.get('targetUid')||'').trim(),durationMinutes:Number(fields.get('durationMinutes'))})});form.elements.enabled.checked=false;show('Der zeitlich begrenzte Vollzugriff wurde aktiviert.');await loadFullAccess();}catch(error){show(error.message||'Der Vollzugriff konnte nicht aktiviert werden.',true);} });
    history?.addEventListener('click',async event=>{const button=event.target.closest('button[data-revoke-uid]');if(!button)return;button.disabled=true;try{await client.request(`/api/admin/full-access/${encodeURIComponent(button.dataset.revokeUid)}`,{method:'DELETE'});show('Der Vollzugriff wurde widerrufen.');await loadFullAccess();}catch(error){button.disabled=false;show(error.message||'Der Vollzugriff konnte nicht widerrufen werden.',true);}});
    loadFullAccess();
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
