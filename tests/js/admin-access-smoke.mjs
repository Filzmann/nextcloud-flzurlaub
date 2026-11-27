import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const overlaySource = readFileSync(new URL('../../js/shared/overlay.js', import.meta.url), 'utf8');
const source = readFileSync(new URL('../../js/admin-access.js', import.meta.url), 'utf8');
const element = () => ({
    checked: false,
    disabled: false,
    hidden: false,
    textContent: '',
    className: '',
    attributes: {},
    focused: false,
    listeners: {},
    dataset: {},
    children: [],
    elements: { enabled: { checked: true } },
    addEventListener(type, listener) { (this.listeners[type] = this.listeners[type] || []).push(listener); },
    append(child) { this.children.push(child); },
    replaceChildren() { this.children = []; },
    setAttribute(name, value) { this.attributes[name] = String(value); },
    getAttribute(name) { return name in this.attributes ? this.attributes[name] : null; },
    focus() { this.focused = true; },
});
const form = element();
const history = element();
const status = element();
const overlay = element();
overlay.hidden = true;
const trigger = element();
const closeButton = element();
const docListeners = {};
const byId = {
    'flz-vacation-full-access-form': form,
    'flz-vacation-full-access-history': history,
    'flz-vacation-full-access-status': status,
    'flz-vacation-admin-access-overlay': overlay,
    'flz-vacation-admin-access-warning': trigger,
    'flz-vacation-admin-access-overlay-close': closeButton,
};
const requests = [];
const context = {
    FormData: class { get(name) { return { enabled: 'on', targetUid: ' admin-target ', durationMinutes: '60' }[name] ?? null; } },
    document: {
        getElementById(id) { return byId[id] || null; },
        createElement() { return element(); },
        addEventListener(type, listener) { (docListeners[type] = docListeners[type] || []).push(listener); },
        querySelectorAll() { return []; },
        head: { appendChild() {} },
    },
    window: { OCA: { FlzUrlaub: { Shared: { api: { ApiClient: class { async request(path, options = {}) { requests.push({ path, options }); return path === '/api/admin/full-access' && !options.method ? { history: [] } : {}; } } } } } } },
    Intl, Date, encodeURIComponent, JSON, Promise, console,
};
runInNewContext(overlaySource, context, { filename: fileURLToPath(new URL('../../js/shared/overlay.js', import.meta.url)) });
runInNewContext(source, context, { filename: fileURLToPath(new URL('../../js/admin-access.js', import.meta.url)) });

const fire = async (el, type, event) => { for (const listener of el.listeners[type] || []) await listener(event); };

await Promise.resolve();
// Ohne geöffnetes Overlay wird keine Historie geladen.
if (requests.length !== 0) throw new Error('Historie wird vor dem Öffnen des Overlays geladen.');

// Warn-Icon öffnet das Overlay und lädt die Historie.
await fire(trigger, 'click', { target: trigger });
if (overlay.hidden !== false) throw new Error('Overlay öffnet nicht.');
if (trigger.getAttribute('aria-expanded') !== 'true') throw new Error('aria-expanded wird nicht gesetzt.');
if (closeButton.focused !== true) throw new Error('Fokus liegt nicht auf dem Schließen-Button.');
if (JSON.stringify(requests.shift()) !== JSON.stringify({ path: '/api/admin/full-access', options: {} })) throw new Error('DPO-Historie wird nicht beim Öffnen geladen.');

await fire(form, 'submit', { preventDefault() {} });
if (JSON.stringify(requests.splice(0, 2)) !== JSON.stringify([
    { path: '/api/admin/full-access', options: { method: 'POST', body: JSON.stringify({ targetUid: 'admin-target', durationMinutes: 60 }) } },
    { path: '/api/admin/full-access', options: {} },
])) throw new Error('Freigabe verwendet nicht den geschützten API-Vertrag.');

const revoke = element();
revoke.dataset.revokeUid = 'admin-target';
await fire(history, 'click', { target: { closest() { return revoke; } } });
if (JSON.stringify(requests.splice(0, 2)) !== JSON.stringify([
    { path: '/api/admin/full-access/admin-target', options: { method: 'DELETE' } },
    { path: '/api/admin/full-access', options: {} },
])) throw new Error('Widerruf verwendet nicht den UID-genauen API-Vertrag.');
if (status.textContent !== 'Der Vollzugriff wurde widerrufen.') throw new Error('Widerruf wird nicht verständlich bestätigt.');

// Escape schließt das Overlay und gibt den Fokus zurück.
for (const listener of docListeners.keydown || []) await listener({ key: 'Escape' });
if (overlay.hidden !== true) throw new Error('Escape schließt das Overlay nicht.');
if (trigger.getAttribute('aria-expanded') !== 'false') throw new Error('aria-expanded wird nicht zurückgesetzt.');
if (trigger.focused !== true) throw new Error('Fokus wird nicht an das Warn-Icon zurückgegeben.');

// Schließen-Button und Backdrop-Klick schließen ebenfalls.
await fire(trigger, 'click', { target: trigger });
await fire(closeButton, 'click', {});
if (overlay.hidden !== true) throw new Error('Schließen-Button schließt nicht.');
await fire(trigger, 'click', { target: trigger });
await fire(overlay, 'click', { target: overlay });
if (overlay.hidden !== true) throw new Error('Backdrop-Klick schließt nicht.');
if (JSON.stringify(requests.splice(0)) !== JSON.stringify([{ path: '/api/admin/full-access', options: {} }, { path: '/api/admin/full-access', options: {} }])) throw new Error('Erneutes Öffnen lädt die Historie nicht erneut.');

console.log('Filzmann Urlaubsplanung admin access overlay smoke: OK');
