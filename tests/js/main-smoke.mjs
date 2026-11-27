import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const mainUrl = new URL('../../js/main.js', import.meta.url);
const source = readFileSync(mainUrl, 'utf8');

const constructed =
{
    apiClient: null,
    notice: null,
    app: null,
};
let started = 0;

const context =
{
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
                                constructed.apiClient = config;
                            }
                        },
                    },
                    ui:
                    {
                        Notice: class
                        {
                            constructor(elementId, options)
                            {
                                constructed.notice =
                                {
                                    elementId,
                                    options,
                                };
                            }
                        },
                    },
                },
            },
        },
        FlzUrlaub:
        {
            modules:
            {
                VacationApp: class
                {
                    constructor(options)
                    {
                        constructed.app = options;
                    }
                    start()
                    {
                        started++;
                    }
                },
            },
        },
    },
    Promise,
    JSON,
    console,
};

runInNewContext(source, context, { filename: fileURLToPath(mainUrl) });

// Positiv: Bootstrap verdrahtet ApiClient, Notice und VacationApp mit dem App-Vertrag.
if (constructed.apiClient?.appId !== 'flzurlaub') throw new Error('ApiClient wird nicht mit appId flzurlaub initialisiert.');
if (typeof constructed.apiClient?.errorMessage !== 'function') throw new Error('ApiClient erhält keine errorMessage-Funktion.');
if (constructed.notice?.elementId !== 'flz-vacation-notice') throw new Error('Notice wird nicht an das Notice-Element gebunden.');
if (constructed.notice?.options?.baseClass !== 'flz-vacation-notice' || constructed.notice?.options?.typeClassPrefix !== 'flz-vacation-notice--')
{
    throw new Error('Notice-Klassenvertrag wird nicht eingehalten.');
}
if (!constructed.app || !constructed.app.client || !constructed.app.notice) throw new Error('VacationApp erhält nicht client und notice.');
if (started !== 1) throw new Error('VacationApp.start() wird genau einmal aufgerufen.');

// Positiv/Negativ: errorMessage bildet API-Fehlermeldung und HTTP-Fallback ab.
const errorMessage = constructed.apiClient.errorMessage;
if (errorMessage({ error: 'Urlaub überschneidet sich.' }, 409) !== 'Urlaub überschneidet sich.') throw new Error('errorMessage übernimmt data.error nicht.');
if (errorMessage({}, 500) !== 'HTTP 500') throw new Error('errorMessage bildet keinen HTTP-Fallback.');
if (errorMessage(null, 403) !== 'HTTP 403') throw new Error('errorMessage verträgt keine leere Antwort.');

console.log('Filzmann Urlaubsplanung main smoke: OK');
