import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const bundleRoot = new URL('../../js/shared/', import.meta.url);
const indexTemplate = readFileSync(new URL('../../templates/index.php', import.meta.url), 'utf8');
const adminTemplate = readFileSync(new URL('../../templates/admin.php', import.meta.url), 'utf8');
const appSources = [
    readFileSync(new URL('../../js/main.js', import.meta.url), 'utf8'),
    readFileSync(new URL('../../js/admin.js', import.meta.url), 'utf8'),
    readFileSync(new URL('../../js/admin-access.js', import.meta.url), 'utf8'),
].join('\n');

for (const registration of [
    "\\OCP\\Util::addScript('flzurlaub', 'shared/api-client')",
    "\\OCP\\Util::addScript('flzurlaub', 'shared/ui')",
    "\\OCP\\Util::addScript('flzurlaub', 'shared/overlay')",
]) {
    if (!(indexTemplate + adminTemplate).includes(registration)) {
        throw new Error(`App-lokale Kategorie-A-Skriptregistrierung fehlt: ${registration}`);
    }
}
for (const foreignReference of [
    "addScript('localbase'",
    "addScript('flzurlaub', 'vendor/flz-shared/",
    'window.LocalBase.',
    '../flz_shared',
]) {
    if ((indexTemplate + adminTemplate + appSources).includes(foreignReference)) {
        throw new Error(`Urlaubs-Bootstrap verwendet weiterhin Kategorie A aus LocalBase: ${foreignReference}`);
    }
}

const lock = JSON.parse(readFileSync(new URL('flz-shared.lock.json', bundleRoot), 'utf8'));
if (lock.component !== 'browser'
    || lock.app_id !== 'flzurlaub'
    || lock.app_namespace !== 'FlzUrlaub'
    || lock.browser_global !== 'window.OCA.FlzUrlaub.Shared'
    || Object.keys(lock.packages || {}).join(',') !== 'browser') {
    throw new Error('Der Browser-Lock beschreibt nicht ausschließlich das isolierte flzurlaub-Bundle.');
}
readFileSync(new URL('LICENSE', bundleRoot), 'utf8');

const requests = [];
const noticeElement = { className: '', hidden: true, textContent: '' };
const window = {
    fetch: async (url, options) => {
        requests.push({ url, options });
        return { ok: true, status: 200, text: async () => '{"result":"ok"}' };
    },
};
window.window = window;
window.OC = {
    generateUrl: path => `/index.php${path}`,
    requestToken: 'vacation-token',
};
const context = vm.createContext({
    document: { getElementById: id => id === 'notice' ? noticeElement : null },
    encodeURIComponent,
    Error,
    JSON,
    Object,
    OC: window.OC,
    String,
    window,
});
for (const file of ['api-client.js', 'model.js', 'overlay.js', 'repository.js', 'ui.js']) {
    const sourcePath = fileURLToPath(new URL(file, bundleRoot));
    vm.runInContext(readFileSync(sourcePath, 'utf8'), context, { filename: sourcePath });
}

const shared = window.OCA?.FlzUrlaub?.Shared;
if (!shared?.api?.ApiClient || !shared?.models?.Model || !shared?.repositories?.Repository || !shared?.ui?.Notice || !shared?.ui?.Overlay || !shared?.ui?.AdminAccessOverlay) {
    throw new Error('Das app-lokale Browser-Bundle stellt seine Primitives nicht vollständig isoliert bereit.');
}
if ('LocalBase' in window) {
    throw new Error('Das app-lokale Browser-Bundle erzeugt ein window.LocalBase-Global.');
}

const client = new shared.api.ApiClient({ appId: 'flzurlaub' });
await client.request('/api/teams');
if (requests.length !== 1
    || requests[0].url !== '/index.php/apps/flzurlaub/api/teams'
    || requests[0].options.headers.requesttoken !== 'vacation-token') {
    throw new Error('Der gebündelte ApiClient bewahrt URL- und CSRF-Vertrag nicht.');
}

const notice = new shared.ui.Notice('notice', {
    baseClass: 'flz-vacation-notice',
    typeClassPrefix: 'flz-vacation-notice--',
});
notice.success('Bereit');
if (noticeElement.textContent !== 'Bereit'
    || noticeElement.hidden
    || noticeElement.className !== 'flz-vacation-notice flz-vacation-notice--success') {
    throw new Error('Die gebündelte Notice bewahrt ihren sichtbaren Erfolgszustand nicht.');
}

console.log('flzurlaub isolated Category-A browser bundle smoke passed');
