import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const holidaySource = readFileSync(new URL('../../js/models/holiday-calendar.js', import.meta.url), 'utf8');
const planSource = readFileSync(new URL('../../js/components/vacation-plan.js', import.meta.url), 'utf8');

class FakeNode {
    constructor(tag = 'div', text = '') {
        this.tagName = tag.toUpperCase();
        this.children = [];
        this.className = '';
        this.dataset = {};
        this.textContent = text;
        this.attributes = {};
        this.classList = { add: value => { if (!this.className.split(' ').includes(value)) this.className = `${this.className} ${value}`.trim(); } };
    }
    append(...children) { this.children.push(...children); }
    replaceChildren(...children) { this.children = children; }
    setAttribute(name, value) { this.attributes[name] = value; }
}

const context = {
    window: {},
    document: {
        createElement: tag => new FakeNode(tag),
        createDocumentFragment: () => new FakeNode('fragment'),
        createTextNode: text => new FakeNode('text', text),
    },
    Date,
};
runInNewContext(holidaySource, context, { filename: fileURLToPath(new URL('../../js/models/holiday-calendar.js', import.meta.url)) });
runInNewContext(planSource, context, { filename: fileURLToPath(new URL('../../js/components/vacation-plan.js', import.meta.url)) });

const holidays = new context.window.FlzUrlaub.models.HolidayCalendar({
    year: 2026,
    fetchedAt: '2026-07-22T12:00:00Z',
    cacheStatus: 'current',
    source: { name: 'OpenHolidays API', license: 'ODbL' },
    schoolHolidays: [{ startDate: '2026-02-02', endDate: '2026-02-07', name: 'Winterferien' }],
    publicHolidays: [{ startDate: '2026-02-05', endDate: '2026-02-05', name: 'Testfeiertag' }],
});
if (holidays.schoolHolidayName('2026-02-02') !== 'Winterferien'
    || holidays.schoolHolidayName('2026-02-07') !== 'Winterferien'
    || holidays.schoolHolidayName('2026-02-08') !== ''
    || holidays.publicHolidayName('2026-02-05') !== 'Testfeiertag') {
    throw new Error('Dynamische Ferien- und Feiertagsdaten werden nicht korrekt gelesen.');
}
const status = new FakeNode();
const elements = { 'holiday-status': status };
const state = { year: 2026, teams: [], teamId: '', plan: { requests: [], holidays: holidays.toArray() } };
const plan = new context.window.FlzUrlaub.components.VacationPlan({ elements, state, holidays });
plan.renderHolidayStatus();
const flatten = node => [node, ...node.children.flatMap(flatten)];
const statusTexts = flatten(status).map(node => node.textContent).join(' ');
if (!statusTexts.includes('OpenHolidays API') || !statusTexts.includes('22.07.2026')) {
    throw new Error('Urlaubsplaner erklärt Quelle und Aktualität der dynamischen Kalenderdaten nicht.');
}

const days = Array.from({ length: 7 }, (_, offset) => ({
    date: `2026-02-${String(offset + 2).padStart(2, '0')}`,
    month: 2,
    dayOfMonth: offset + 2,
    weekday: offset + 1,
}));
const [holidayBand, publicHolidayBand, dayHeader] = plan.renderHeader(days);
const winterBand = holidayBand.children[1];
if (!holidayBand.className.includes('flz-vacation-school-holiday-row')
    || winterBand.textContent !== ''
    || winterBand.children[0]?.textContent !== 'Winterferien'
    || !winterBand.children[0]?.className.includes('flz-vacation-holiday-label')
    || winterBand.colSpan !== 6
    || winterBand.title !== 'Winterferien'
    || !winterBand.className.includes('is-school-holiday')
    || !winterBand.attributes['aria-label'].includes('02.02.2026–07.02.2026')) {
    throw new Error('Zusammenhängende Schulferien bilden im Tabellenkopf keinen benannten Farbstreifen.');
}
const holidayHeading = dayHeader.children[1];
if (!holidayHeading.attributes['aria-label'].includes('Winterferien') || holidayHeading.className.includes('is-school-holiday')) throw new Error('Nur das Ferienband darf farblich hervorgehoben sein.');
const saturdayHeading = dayHeader.children[6];
const sundayHeading = dayHeader.children[7];
if (!saturdayHeading.className.includes('is-saturday')
    || saturdayHeading.className.includes('is-sunday')
    || !saturdayHeading.attributes['aria-label'].includes('Samstag')
    || !sundayHeading.className.includes('is-sunday')
    || sundayHeading.className.includes('is-saturday')
    || !sundayHeading.attributes['aria-label'].includes('Sonntag')) {
    throw new Error('Samstag und Sonntag werden nicht getrennt und zugänglich gekennzeichnet.');
}
const publicHoliday = publicHolidayBand.children[2];
if (publicHoliday.textContent !== ''
    || publicHoliday.children[0]?.textContent !== 'Testfeiertag'
    || !publicHoliday.children[0]?.className.includes('flz-vacation-holiday-label')
    || publicHoliday.colSpan !== 1
    || publicHoliday.title !== 'Testfeiertag'
    || !publicHoliday.className.includes('is-public-holiday')) {
    throw new Error('Gesetzliche Feiertage bilden kein eigenes benanntes Farbband.');
}
const day = days[0];
const cell = plan.renderDay({ uid: 'person-a', displayName: 'Person A', canApprove: true }, day);
if (cell.className.includes('is-school-holiday') || !cell.children[0].attributes['aria-label'].includes('Winterferien')) {
    throw new Error('Personenzellen werden als Ferien eingefärbt oder verlieren ihre zugängliche Textkennzeichnung.');
}
const publicDayCell = plan.renderDay({ uid: 'person-a', displayName: 'Person A', canApprove: true }, days[3]);
if (publicDayCell.className.split(' ').includes('is-public-holiday')
    || !publicDayCell.className.includes('is-public-holiday-column')
    || !publicDayCell.children[0].attributes['aria-label'].includes('Testfeiertag')) {
    throw new Error('Feiertagsspalten verwenden die Bandklasse oder verlieren ihre zugängliche Textkennzeichnung.');
}
const saturdayCell = plan.renderDay({ uid: 'person-a', displayName: 'Person A', canApprove: true }, days[5]);
const sundayCell = plan.renderDay({ uid: 'person-a', displayName: 'Person A', canApprove: true }, days[6]);
if (!saturdayCell.className.includes('is-saturday')
    || saturdayCell.className.includes('is-sunday')
    || !sundayCell.className.includes('is-sunday')
    || sundayCell.className.includes('is-saturday')) {
    throw new Error('Samstags- und Sonntagsspalten werden in Personenzeilen nicht getrennt markiert.');
}

for (const [date, name] of [['2026-12-24', 'Heiligabend'], ['2026-12-31', 'Silvester']]) {
    const specialDay = { date, month: 12, dayOfMonth: Number(date.slice(-2)), weekday: 4 };
    const specialHeading = plan.renderDayHeader([specialDay]).children[1];
    const specialCell = plan.renderDay({ uid: 'person-a', displayName: 'Person A', canApprove: true }, specialDay);
    if (!specialHeading.className.includes('is-year-end-special')
        || specialHeading.className.includes('is-public-holiday-column')
        || !specialHeading.attributes['aria-label'].includes(name)
        || !specialCell.className.includes('is-year-end-special')
        || specialCell.className.includes('is-public-holiday-column')
        || !specialCell.children[0].attributes['aria-label'].includes(name)) {
        throw new Error(`${name} wird nicht eigenständig und zugänglich vom gesetzlichen Feiertag abgegrenzt.`);
    }
}

holidays.replace({ schoolHolidays: [{ startDate: 'ungültig', endDate: '2026-02-07', name: 'Falsch' }] });
if (holidays.schoolHolidayName('2026-02-02') !== '') throw new Error('Ungültige Serverdaten werden im Browser dargestellt.');

state.plan.holidays.cacheStatus = 'stale';
plan.holidays.replace(state.plan.holidays);
plan.renderHolidayStatus();
if (!status.textContent.includes('letzten erfolgreichen Abruf')) {
    throw new Error('Veraltete Kalenderdaten werden nicht transparent erklärt.');
}

console.log('Berlin holiday calendar smoke: OK');
