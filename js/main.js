(function () {
    'use strict';

    const client = new window.OCA.FlzUrlaub.Shared.api.ApiClient({
        appId: 'flzurlaub',
        errorMessage: (data, status) => data?.error || `HTTP ${status}`,
    });
    const notice = new window.OCA.FlzUrlaub.Shared.ui.Notice('flz-vacation-notice', {
        baseClass: 'flz-vacation-notice',
        typeClassPrefix: 'flz-vacation-notice--',
    });

    new window.FlzUrlaub.modules.VacationApp({ client, notice }).start();
}());
