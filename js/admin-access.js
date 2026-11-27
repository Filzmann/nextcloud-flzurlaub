(function() {
    'use strict';

    const shared = window.OCA.FlzUrlaub?.Shared?.ui;
    if (!shared?.AdminAccessOverlay || !window.OCA.FlzUrlaub?.Shared?.api?.ApiClient) return;

    const client = new window.OCA.FlzUrlaub.Shared.api.ApiClient({ appId: 'flzurlaub' });

    new shared.AdminAccessOverlay({
        overlayId: 'flz-vacation-admin-access-overlay',
        triggerId: 'flz-vacation-admin-access-warning',
        closeId: 'flz-vacation-admin-access-overlay-close',
        formId: 'flz-vacation-full-access-form',
        historyId: 'flz-vacation-full-access-history',
        statusId: 'flz-vacation-full-access-status',
        api: {
            getState: () => client.request('/api/admin/full-access'),
            activate: (targetUid, durationMinutes) => client.request('/api/admin/full-access', {
                method: 'POST',
                body: JSON.stringify({ targetUid, durationMinutes }),
            }),
            revoke: (targetUid) => client.request(`/api/admin/full-access/${encodeURIComponent(targetUid)}`, { method: 'DELETE' }),
        },
    });
}());
