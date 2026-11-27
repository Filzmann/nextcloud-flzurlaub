import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const adminUrl = new URL('../../js/admin.js', import.meta.url);
const source = readFileSync(adminUrl, 'utf8');

const element = () =>
({
    checked: false,
    disabled: false,
    hidden: true,
    textContent: '',
    className: '',
    classes: new Set(),
    classList: null,
    listeners:
    {
    },
    addEventListener(type, listener)
    {
        this.listeners[type] = listener;
    },
});

function makeClassList(el)
{
    return (
    {
        add: (...names) => names.forEach(name => el.classes.add(name)),
    });
}

function buildContext({ elements, requests, failWith = null })
{
    for (const el of Object.values(elements)) el.classList = makeClassList(el);
    return (
    {
        document:
        {
            getElementById: id => elements[id] || null,
        },
        window:
        {
            OCA:
            {
                FlzUrlaub:
                {
                    Shared:
                    {
                        api:
                        {
                            ApiClient: class
                            {
                                constructor(config)
                                {
                                    this.config = config;
                                }
                                async request(path, options = {})
                                {
                                    requests.push(
                                    {
                                        path,
                                        options,
                                    });
                                    if (failWith)
                                    {
                                        throw failWith;
                                    }
                                    return (
                                    {
                                        result:
                                        {
                                            createdVacations: 7,
                                            coveredGroups: 3,
                                        },
                                    });
                                }
                            },
                        },
                    },
                },
            },
        },
        Promise,
        JSON,
        console,
    });
}

async function flush(times = 10)
{
    for (let index = 0; index < times; index++)
    {
        await Promise.resolve();
    }
}

// Positiv: change schaltet den Installationsbutton frei und wieder zu.
{
    const confirmation = element();
    const button = element();
    button.disabled = true;
    const notice = element();
    const requests = [];
    const context = buildContext(
    {
        elements:
        {
            'flz-vacation-demo-confirm': confirmation,
            'flz-vacation-demo-install': button,
            'flz-vacation-demo-notice': notice,
        },
        requests,
    });
    runInNewContext(source, context, { filename: fileURLToPath(adminUrl) });
    confirmation.checked = true;
    confirmation.listeners.change();
    if (button.disabled !== false) throw new Error('Bestätigtes Häkchen aktiviert den Installationsbutton nicht.');
    confirmation.checked = false;
    confirmation.listeners.change();
    if (button.disabled !== true) throw new Error('Entferntes Häkchen sperrt den Installationsbutton nicht wieder.');
    if (requests.length !== 0) throw new Error('Das Umschalten allein darf keine API-Anfrage auslösen.');
}

// Negativ: Klick ohne bestätigtes Häkchen löst keine Anfrage aus.
{
    const confirmation = element();
    const button = element();
    const notice = element();
    const requests = [];
    const context = buildContext(
    {
        elements:
        {
            'flz-vacation-demo-confirm': confirmation,
            'flz-vacation-demo-install': button,
            'flz-vacation-demo-notice': notice,
        },
        requests,
    });
    runInNewContext(source, context, { filename: fileURLToPath(adminUrl) });
    await button.listeners.click();
    if (requests.length !== 0) throw new Error('Klick ohne Bestätigung darf keine Installation anstoßen.');
    if (notice.hidden !== true) throw new Error('Klick ohne Bestätigung darf keine Hinweismeldung zeigen.');
}

// Positiv: bestätigter Klick ruft den geschützten Endpunkt auf und meldet Erfolg.
{
    const confirmation = element();
    confirmation.checked = true;
    const button = element();
    const notice = element();
    const requests = [];
    const context = buildContext(
    {
        elements:
        {
            'flz-vacation-demo-confirm': confirmation,
            'flz-vacation-demo-install': button,
            'flz-vacation-demo-notice': notice,
        },
        requests,
    });
    runInNewContext(source, context, { filename: fileURLToPath(adminUrl) });
    const click = button.listeners.click();
    if (button.disabled !== true || notice.hidden !== false || notice.textContent !== 'Demo-Pack wird geprüft und installiert …')
    {
        throw new Error('Während der Installation müssen Button gesperrt und Fortschrittshinweis sichtbar sein.');
    }
    await click;
    await flush();
    if (JSON.stringify(requests) !== JSON.stringify(
    [
        {
            path: '/api/admin/demo-pack/install',
            options:
            {
                method: 'POST',
                body: '{}',
            },
        },
    ]))
    {
        throw new Error('Demo-Pack-Installation verwendet nicht den erwarteten API-Vertrag.');
    }
    if (!notice.classes.has('is-success')) throw new Error('Erfolgreiche Installation muss is-success setzen.');
    if (notice.textContent !== '7 Urlaube angelegt; 3 Fachgruppen abgedeckt.') throw new Error('Erfolgsmeldung unterschlägt die gemeldeten Anzahlen.');
    if (confirmation.checked !== false) throw new Error('Nach Erfolg muss das Bestätigungs-Häkchen zurückgesetzt werden.');
}

// Negativ: API-Fehler wird verständlich gemeldet und der Button wieder freigegeben.
{
    const confirmation = element();
    confirmation.checked = true;
    const button = element();
    const notice = element();
    const requests = [];
    const context = buildContext({
        elements: { 'flz-vacation-demo-confirm': confirmation, 'flz-vacation-demo-install': button, 'flz-vacation-demo-notice': notice },
        requests,
        failWith: new Error('Demo-Pack ist nicht verfügbar.'),
    });
    runInNewContext(source, context, { filename: fileURLToPath(adminUrl) });
    await button.listeners.click();
    await flush();
    if (requests.length !== 1) throw new Error('Fehlerfall muss genau eine API-Anfrage auslösen.');
    if (!notice.classes.has('is-error')) throw new Error('Fehlerfall muss is-error setzen.');
    if (notice.textContent !== 'Demo-Pack ist nicht verfügbar.') throw new Error('Fehlermeldung der API muss übernommen werden.');
    if (button.disabled !== false) throw new Error('Nach Fehler muss der Button wieder bedienbar sein.');
    if (confirmation.checked !== true) throw new Error('Nach Fehler darf das Häkchen nicht zurückgesetzt werden.');
}

// Negativ: ohne die erwarteten Elemente beendet sich das Skript ohne Fehler.
{
    const context = buildContext(
    {
        elements:
        {
        },
        requests: [],
    });
    runInNewContext(source, context, { filename: fileURLToPath(adminUrl) });
}

console.log('Filzmann Urlaubsplanung admin smoke: OK');
